<?php
require_once 'baseinfo.php';

$apiUrl = "https://api.telegram.org/bot$botToken/";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

function ensureSettingExists($conn) {
    $sql = "INSERT IGNORE INTO setting (id, cardnumber, cardownername) VALUES (1, '6037-6037-6037-6037', 'کندو پنل')";
    $conn->query($sql);
}

ensureSettingExists($conn);

$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

$chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'];
$message = $update['message']['text'] ?? '';
$callbackData = $update['callback_query']['data'] ?? '';

function getBlockedUsers($conn) {
    $sql = "SELECT chatid FROM black_list";
    $result = $conn->query($sql);
    $users = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row['chatid'];
        }
    }
    return $users;
}

function blockUser($userId, $conn) {
    $sql = "INSERT IGNORE INTO black_list (chatid) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function unblockUser($userId, $conn) {
    $sql = "DELETE FROM black_list WHERE chatid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function updateCardNumber($newCardNumber, $conn) {
    $sql = "UPDATE setting SET cardnumber = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $newCardNumber);
    $stmt->execute();
    $stmt->close();
}

function updateCardOwnerName($newOwnerName, $conn) {
    $sql = "UPDATE setting SET cardownername = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $newOwnerName);
    $stmt->execute();
    $stmt->close();
}

function ensureUserExists($conn, $chatId) {
    $query = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $query->bind_param("i", $chatId);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        $insertQuery = $conn->prepare("INSERT INTO users (id, wallet, number) VALUES (?, 0, '')");
        $insertQuery->bind_param("i", $chatId);
        $insertQuery->execute();
        $insertQuery->close();
    }
    $query->close();
}

$blockedUsers = getBlockedUsers($conn);

if ($message == "/start" && !in_array($chatId, $blockedUsers)) {
    ensureUserExists($conn, $chatId);
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'دکمه 1', 'callback_data' => 'button1']],
            [['text' => '💳شارژ کیف پول', 'callback_data' => 'wallet_charge']],
        ]
    ];

    if ($chatId == $adminId) {
        $keyboard['inline_keyboard'][] = [
            ['text' => 'پنل ادمین', 'callback_data' => 'admin_panel']
        ];
    }

    $replyMarkup = json_encode($keyboard);

    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("سلام! خوش اومدی به ربات ما! 😊") . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "admin_panel" && $chatId == $adminId) {
    $editText = "عزیزم به پنل ادمین خوش اومدی 😊";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'بخش پرداخت', 'callback_data' => 'payment_settings'], ['text' => 'مدیریت کاربران', 'callback_data' => 'user_management']]
        ]
    ];

    $replyMarkup = json_encode($keyboard);
    $messageId = $update['callback_query']['message']['message_id'];

    file_get_contents($apiUrl . "editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($editText) . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "payment_settings" && $chatId == $adminId) {
    $sql = "SELECT cardnumber, cardownername FROM setting WHERE id = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $cardNumber = $row['cardnumber'];
    $cardOwnerName = $row['cardownername'];
    
    if ($cardNumber === null || $cardOwnerName === null) {
        $cardNumber = 'شماره کارت وجود ندارد';
        $cardOwnerName = 'نام صاحب کارت وجود ندارد';
    }

    $editText = "اینجا بخش پرداخت است. شماره کارت فعلی: $cardNumber\nمالک کارت: $cardOwnerName";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => "$cardNumber", 'callback_data' => 'change_card_number'], ['text' => 'شماره کارت', 'callback_data' => 'dummy']],
            [['text' => "$cardOwnerName", 'callback_data' => 'change_card_owner_name'], ['text' => 'نام مالک کارت', 'callback_data' => 'dummy']]
        ]
    ];

    $replyMarkup = json_encode($keyboard);
    $messageId = $update['callback_query']['message']['message_id'];

    file_get_contents($apiUrl . "editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($editText) . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "change_card_number" && $chatId == $adminId) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً شماره کارت جدید را وارد کنید:"));
} elseif ($callbackData == "change_card_owner_name" && $chatId == $adminId) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً نام مالک جدید را وارد کنید:"));
} elseif (!empty($message) && $chatId == $adminId) {
    if (preg_match('/^\d{4}-\d{4}-\d{4}-\d{4}$/', $message)) {
        updateCardNumber($message, $conn);
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("شماره کارت به $message تغییر کرد."));
    } else {
        updateCardOwnerName($message, $conn);
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("نام مالک کارت به $message تغییر کرد."));
    }
}

$conn->close();
?>
