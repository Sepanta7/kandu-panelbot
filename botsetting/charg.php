<?php
require_once 'baseinfo.php';

$apiUrl = "https://api.telegram.org/bot$botToken/";    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);
$message = $update['message']['text'] ?? '';
$callbackData = $update['callback_query']['data'] ?? '';

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
