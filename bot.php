<?php
require_once 'baseinfo.php';

$apiUrl = "https://api.telegram.org/bot$botToken/";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

function ensureSettingExists($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS setting (id INT PRIMARY KEY, starttxt TEXT)");
    $sql = "INSERT IGNORE INTO setting (id, cardnumber, cardownername, starttxt) VALUES (1, '6037-6037-6037-6037', 'کندو پنل', 'سلام به ربات ما خوش آمدید دوست عزیز❤️')";
    $conn->query($sql);
}

function getStartText($conn) {
    $sql = "SELECT starttxt FROM setting WHERE id = 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc()['starttxt'] ?? "سلام به ربات ما خوش آمدید دوست عزیز❤️";
}

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

function checkAndCreateUser($conn, $chatId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("s", $chatId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO users (id, wallet, number) VALUES (?, 0, NULL)");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
    }
    $stmt->close();
}

function blockUser($conn, $chatId) {
    $stmt = $conn->prepare("INSERT INTO black_list (chatid) VALUES (?)");
    $stmt->bind_param("s", $chatId);
    $stmt->execute();
    $stmt->close();
}

function unblockUser($conn, $chatId) {
    $stmt = $conn->prepare("DELETE FROM black_list WHERE chatid = ?");
    $stmt->bind_param("s", $chatId);
    $stmt->execute();
    $stmt->close();
}

ensureSettingExists($conn);

$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

$chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'];
$message = $update['message']['text'] ?? '';
$callbackData = $update['callback_query']['data'] ?? '';

$blockedUsers = getBlockedUsers($conn);

if ($message == "/start" && !in_array($chatId, $blockedUsers)) {
    checkAndCreateUser($conn, $chatId);

    $startText = getStartText($conn);
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
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($startText) . "&reply_markup=$replyMarkup");
} elseif ($callbackData == "wallet_charge" && !in_array($chatId, $blockedUsers)) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً روش افزایش موجودی را انتخاب کنید:") . "&reply_markup=" . json_encode([
        'inline_keyboard' => [[['text' => '💳 کارت به کارت', 'callback_data' => 'card_to_card']]]
    ]));
} elseif ($callbackData == "card_to_card") {
    include 'botsetting/charg.php';
} elseif ($callbackData == "admin_panel" && $chatId == $adminId) {
    $editText = "عزیزم به پنل ادمین خوش اومدی 😊";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'بخش پرداخت', 'callback_data' => 'payment_settings'],
             ['text' => 'مدیریت کاربران', 'callback_data' => 'user_management']]
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

    $editText = "اینجا بخش پرداخت است میتونی طبق نیاز هات تنظیمات رو تغییر بدی";
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => "$cardNumber", 'callback_data' => 'change_card_number'],
             ['text' => 'شماره کارت', 'callback_data' => 'dummy']],
            [['text' => "$cardOwnerName", 'callback_data' => 'change_card_owner_name'],
             ['text' => 'نام مالک کارت', 'callback_data' => 'dummy']]
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
