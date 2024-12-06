<?php

include 'baseinfo.php';


$update = json_decode(file_get_contents('php:
$chatId = $update['message']['chat']['id'];
$firstName = $update['message']['chat']['first_name'];
$lastName = $update['message']['chat']['last_name'] ?? null;
$username = $update['message']['chat']['username'] ?? 'نام کاربری ندارد';
$messageText = $update['message']['text'];

if ($messageText == "/start") {
    $startText = "سلام! لطفاً یکی از گزینه‌های زیر را انتخاب کنید:";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🛒 خرید کانفیگ جدید', 'callback_data' => 'buy_config'],
                ['text' => '🗃️ سرویس‌های من', 'callback_data' => 'my_services']
            ],
            [
                ['text' => '💳 شارژ کیف پول در ربات', 'callback_data' => 'charge_wallet']
            ],
            [
                ['text' => '👤 حساب من', 'callback_data' => 'my_account'],
                ['text' => '🧩 آموزش اتصال', 'callback_data' => 'connection_guide'],
                ['text' => '⚙️ مدیریت', 'callback_data' => 'manage_panel'],
            ]
        ]
    ];

    sendMessage($chatId, $startText, $keyboard);
}


if (isset($update['callback_query'])) {
    $callbackData = $update['callback_query']['data'];
    $chatId = $update['callback_query']['message']['chat']['id'];

    if ($callbackData == "buy_config") {
        sendMessage($chatId, "لطفاً برای خرید کانفیگ به وب‌سایت مراجعه کنید.");
    }

    if ($callbackData == "my_services") {
        sendMessage($chatId, "شما در حال حاضر هیچ سرویسی ندارید.");
    }

    if ($callbackData == "charge_wallet") {
        sendMessage($chatId, "برای شارژ کیف پول لطفاً از بخش پرداخت استفاده کنید.");
    }

    if ($callbackData == "connection_guide") {
        sendMessage($chatId, "آموزش اتصال در وب‌سایت موجود است.");
    }

    if ($callbackData == "manage_panel") {
        if ($chatId == $adminChatId) {
            sendMessage($chatId, "پنل مدیریت باز شد.");
        } else {
            sendMessage($chatId, "شما دسترسی به پنل مدیریت ندارید.");
        }
    }

    if ($callbackData == "my_account") {
    $sql_user = "SELECT * FROM users WHERE chat_id = '$chatId'";
    $result_user = $conn->query($sql_user);

    if ($result_user->num_rows > 0) {
        $row_user = $result_user->fetch_assoc();

        $name = $row_user['first_name'] . " " . $row_user['last_name'];
        $wallet = number_format($row_user['wallet']);
        $acounts = $row_user['accounts'] ?? 0;
        $phone = $row_user['phone'] ?? '🔴تایید نشده🔴';

        $accountText = "📇 دوست عزیز مشخصات حساب شما به شرح زیر می‌باشد:\n\n";
        $accountText .= "<blockquote>👤 نام شما: $name\n";
        $accountText .= "🏆 آیدی عددی شما: $chatId\n";
        $accountText .= "🆔 یوزرنیم شما: @$username\n";  
        $accountText .= "💲 موجودی کیف پول شما: $wallet تومان\n";
        $accountText .= "📦 تعداد خرید‌های شما: $acounts\n";
        $accountText .= "📱 شماره تلفن شما: $phone</blockquote>\n";

        sendMessage($chatId, $accountText);
    } else {
        sendMessage($chatId, "⛔️ حسابی برای شما یافت نشد. لطفاً ابتدا /start را ارسال کنید.");
    }
}

}


function sendMessage($chatId, $text, $keyboard = null)
{
    global $apiUrl;
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    $response = file_get_contents($apiUrl . "sendMessage?" . http_build_query($data));
    $responseData = json_decode($response, true);
    return $responseData['result']['message_id'] ?? null;
}
?>
<!--  -->
