<?php
require_once 'baseinfo.php';

$apiUrl = "https://api.telegram.org/bot$botToken/";
$dataDir = __DIR__ . '/data';
$banFile = $dataDir . '/ban_users.txt';

if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

if (!file_exists($banFile)) {
    file_put_contents($banFile, "");
}

$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

$chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'];
$message = $update['message']['text'] ?? '';
$callbackData = $update['callback_query']['data'] ?? '';

function getBlockedUsers($banFile) {
    $users = file($banFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return $users ? $users : [];
}

function blockUser($userId, $banFile) {
    $blockedUsers = getBlockedUsers($banFile);
    if (!in_array($userId, $blockedUsers)) {
        file_put_contents($banFile, $userId . PHP_EOL, FILE_APPEND);
    }
}

function unblockUser($userId, $banFile) {
    $blockedUsers = getBlockedUsers($banFile);
    if (in_array($userId, $blockedUsers)) {
        $updatedUsers = array_diff($blockedUsers, [$userId]);
        file_put_contents($banFile, implode(PHP_EOL, $updatedUsers) . PHP_EOL);
    }
}

$blockedUsers = getBlockedUsers($banFile);

if ($message == "/start" && !in_array($chatId, $blockedUsers)) {
    $text = "سلام! خوش اومدی به ربات ما! 😊\n\nامیدوارم از استفاده از ربات لذت ببری. لطفاً یکی از گزینه‌های زیر رو انتخاب کن:";

    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'دکمه 1', 'callback_data' => 'button1']],
            [['text' => 'دکمه 2', 'callback_data' => 'button2']],
        ]
    ];

    if ($chatId == $adminId) {
        $keyboard['inline_keyboard'][] = [
            ['text' => 'پنل ادمین', 'callback_data' => 'admin_panel']
        ];
    }

    $replyMarkup = json_encode($keyboard);

    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($text) . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "admin_panel" && $chatId == $adminId) {
    $editText = "عزیزم به پنل ادمین خوش اومدی گلم 😊\n\nراستی اگه از ربات راضی هستی بیا داخل کانال سازندم جوین شو لطفا تا بتونی آپدیت‌ها رو انجام بدی!\n@kandu_ch";

    $keyboard = [
        'inline_keyboard' => [
            [['text' => '❌مسدود کردن کاربر❌', 'callback_data' => 'block_user']],
            [['text' => '✅رفع مسدودی کاربر✅', 'callback_data' => 'unblock_user']]
        ]
    ];

    $replyMarkup = json_encode($keyboard);

    $messageId = $update['callback_query']['message']['message_id'];

    file_get_contents($apiUrl . "editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($editText) . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "block_user" && $chatId == $adminId) {
    file_put_contents("$dataDir/is_blocking.txt", "1");
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً آیدی عددی کاربر را ارسال کنید:"));

} elseif (file_exists("$dataDir/is_blocking.txt") && $chatId == $adminId) {
    $userIdToBlock = $message;

    if ($userIdToBlock == $adminId) {
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("خودت میخوای خودتو بن کنی؟😑😐"));
    } elseif (userHasStartedBot($userIdToBlock)) {
        blockUser($userIdToBlock, $banFile);
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("کاربر $userIdToBlock مسدود گردید✅"));
    } else {
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("آیدی عددی که وارد کردی اشتباست🤣 دوباره بفرس."));
    }

    unlink("$dataDir/is_blocking.txt");

} elseif ($callbackData == "unblock_user" && $chatId == $adminId) {
    file_put_contents("$dataDir/is_unblocking.txt", "1");
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً آیدی عددی کاربر را ارسال کنید:"));

} elseif (file_exists("$dataDir/is_unblocking.txt") && $chatId == $adminId) {
    $userIdToUnblock = $message;

    if ($userIdToUnblock == $adminId) {
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("خودت میخوای خودتو بن کنی؟😑😐"));
    } elseif (!in_array($userIdToUnblock, $blockedUsers)) {
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("این کاربر مسدود نیس😄👈👉"));
    } elseif (userHasStartedBot($userIdToUnblock)) {
        unblockUser($userIdToUnblock, $banFile);
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("کاربر $userIdToUnblock آزاد شد✅"));
    } else {
        file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("آیدی عددی که وارد کردی اشتباست🤣 دوباره بفرس."));
    }

    unlink("$dataDir/is_unblocking.txt");

} elseif (in_array($chatId, $blockedUsers)) {
    exit;
}

function userHasStartedBot($userId) {
    global $apiUrl;
    $response = file_get_contents($apiUrl . "getChat?chat_id=$userId");
    $data = json_decode($response, TRUE);
    return isset($data['ok']) && $data['ok'];
}
?>
