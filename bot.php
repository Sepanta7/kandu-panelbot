<?php
require_once 'baseinfo.php';

$apiUrl = "https://api.telegram.org/bot$botToken/";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

function ensureSettingExists($conn) {
    $sql = "INSERT IGNORE INTO setting (id, cardnumber, cardownername, authentication) VALUES (1, '6037-6037-6037-6037', 'کندو پنل', 'no')";
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
    $sql = "SELECT authentication FROM setting WHERE id = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $authenticationStatus = $row['authentication'] === 'yes' ? '☑️' : '❌';
    $descriptionText = "🔰 عزیزم اینجا بخش تنظیمات عمومیه که میتونی طبق نیاز هات تغییرش بدی:";

    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'احراز هویت شماره تلفن', 'callback_data' => 'dummy'], ['text' => $authenticationStatus, 'callback_data' => 'toggle_authentication']]
        ]
    ];
    
    $replyMarkup = json_encode($keyboard);
    $messageId = $update['callback_query']['message']['message_id'];
    file_get_contents($apiUrl . "editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($descriptionText) . "&reply_markup=$replyMarkup");
    
} elseif ($callbackData == "toggle_authentication" && $chatId == $adminId) {
    $sql = "SELECT authentication FROM setting WHERE id = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $currentStatus = $row['authentication'];
    $newStatus = $currentStatus === 'yes' ? 'no' : 'yes';
    $updateSql = "UPDATE setting SET authentication = '$newStatus' WHERE id = 1";
    $conn->query($updateSql);
    $newEmojiStatus = $newStatus === 'yes' ? '☑️' : '❌';
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'احراز هویت شماره تلفن', 'callback_data' => 'dummy'], ['text' => $newEmojiStatus, 'callback_data' => 'toggle_authentication']]
        ]
    ];
    $replyMarkup = json_encode($keyboard);
    $messageId = $update['callback_query']['message']['message_id'];
    file_get_contents($apiUrl . "editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($descriptionText) . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "wallet_charge" && !in_array($chatId, $blockedUsers)) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("🔄 لطفا مقدار شارژ خود را وارد کنید:") . "&reply_markup={\"remove_keyboard\":true}");
}

$conn->close();
?>
