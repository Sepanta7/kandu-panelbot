<?php
require_once 'baseinfo.php';

$apiUrl = "https://api.telegram.org/bot$botToken/";    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

$chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'];
$message = $update['message']['text'] ?? '';
$callbackData = $update['callback_query']['data'] ?? '';

if ($callbackData == "card_to_card") {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("لطفاً مبلغ مورد نظر را وارد کنید:"));
} elseif (!empty($message) && preg_match('/^\d+$/', $message)) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("🧧 در حال پردازش...."));
    sleep(6);
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode("🤖 عزیزم روش افزایش موجودی را انتخاب کن:") . "&reply_markup=" . json_encode([
        'inline_keyboard' => [[['text' => '💳 کارت به کارت', 'callback_data' => 'card_to_card']]]
    ]));

    $amount = number_format(floatval($message), 0, '', '.');

    $sql = "SELECT cardnumber, cardownername FROM setting WHERE id = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $cardNumber = $row['cardnumber'];
    $cardOwnerName = $row['cardownername'];

    $messageText = "☑️ عزیز دلم به این شماره کارت مبلغ $amount را واریز کن👇\n`$cardNumber`\nبه نام: $cardOwnerName";
    
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($messageText) . "&parse_mode=MarkdownV2");
}

$conn->close();
?>
