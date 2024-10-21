<?php
require_once 'baseinfo.php';

$apiUrl = "https://api.telegram.org/bot$botToken/";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

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

$blockedUsers = getBlockedUsers($conn);

if ($message == "/start" && !in_array($chatId, $blockedUsers)) {
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
            [
                ['text' => '❌مسدود کردن کاربر❌', 'callback_data' => 'block_user'],
                ['text' => '✅رفع مسدودی کاربر✅', 'callback_data' => 'unblock_user']
            ]
        ]
    ];

    $replyMarkup = json_encode($keyboard);

    $messageId = $update['callback_query']['message']['message_id'];

    file_get_contents($apiUrl . "editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($editText) . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "block_user" && $chatId == $adminId) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً آیدی عددی کاربر را ارسال کنید:"));

} elseif ($callbackData == "unblock_user" && $chatId == $adminId) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً آیدی عددی کاربر را ارسال کنید:"));

} elseif (is_numeric($message) && $chatId == $adminId) {
    if (in_array($message, $blockedUsers)) {
        unblockUser($message, $conn);
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("کاربر $message از مسدودی خارج شد."));
    } else {
        blockUser($message, $conn);
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("کاربر $message مسدود شد."));
    }
} elseif ($callbackData == "wallet_charge" && !in_array($chatId, $blockedUsers)) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفا مبلغ را وارد کنید (بین 5000 تومان تا 5000000 تومان):"));

} elseif (is_numeric($message) && !in_array($chatId, $blockedUsers)) {
    if ($message >= 5000 && $message <= 5000000) {
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("مبلغ $message تومان با موفقیت ثبت شد."));
    } else {
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("مبلغ وارد شده معتبر نیست. لطفا عددی بین 5000 تا 5000000 تومان وارد کنید."));
    }
}

$conn->close();
?>
