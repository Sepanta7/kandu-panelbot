<?php
require_once 'baseinfo.php';  // اطلاعات ربات

$apiUrl = "https://api.telegram.org/bot$botToken/";

// دریافت آپدیت‌های ربات
$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

$chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'];
$message = $update['message']['text'] ?? '';
$callbackData = $update['callback_query']['data'] ?? '';

if ($message == "/start") {
    // پیام استارت
    $text = "سلام! خوش آمدید. لطفا یکی از گزینه‌های زیر را انتخاب کنید.";

    // دکمه‌های اینلاین
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'دکمه 1', 'callback_data' => 'button1']],
            [['text' => 'دکمه 2', 'callback_data' => 'button2']],
        ]
    ];

    // اگر چت آیدی برابر با آیدی ادمین باشد، دکمه پنل ادمین اضافه می‌شود
    if ($chatId == $adminId) {
        $keyboard['inline_keyboard'][] = [['text' => 'پنل ادمین', 'callback_data' => 'admin_panel']];
    }

    $replyMarkup = json_encode($keyboard);

    // ارسال پیام
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($text) . "&reply_markup=$replyMarkup");

} elseif ($callbackData == "admin_panel" && $chatId == $adminId) {
    // ویرایش پیام برای ادمین
    $editText = "عزیزم به پنل ادمین خوش اومدی گلم 😊\n\nراستی اگه از ربات راضی هستی بیا داخل کانال سازندم جوین شو لطفا تا بتونی آپدیت‌ها رو انجام بدی!\n@kandu_ch";

    $messageId = $update['callback_query']['message']['message_id'];

    file_get_contents($apiUrl . "editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($editText));
}
?>
