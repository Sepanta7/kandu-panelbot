<?php
$botToken = "توکن";
$apiUrl = "https://api.telegram.org/bot$botToken/";

$adminChatId = ""; // آیدی چت ادمین

$servername = "localhost";
$username = "یوزرنیم دیتابیس";
$password = "پسورد دیتابیس";
$dbname = "نام دیتابیس";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$update = json_decode(file_get_contents('php://input'), true);
$chatId = $update['message']['chat']['id'];
$firstName = $update['message']['chat']['first_name'];
$lastName = $update['message']['chat']['last_name'] ?? null;
$username = $update['message']['chat']['username'] ?? 'نام کاربری ندارد';
$messageText = $update['message']['text'];

$wallet = 0;

if ($messageText == "/start") {
    $sql_check = "SELECT * FROM users WHERE chat_id = '$chatId'";
    $result = $conn->query($sql_check);

    if ($result->num_rows == 0) {
        $sql_insert = "INSERT INTO users (chat_id, first_name, last_name, wallet) VALUES ('$chatId', '$firstName', '$lastName', '$wallet')";
        $conn->query($sql_insert);

        $userDetails = "<b>گلم ی یوزر جدید رباتت رو استارت کرده😍</b>\n\n";
        $userDetails .= "<code>🆔 آیدی عددیش: $chatId</code>\n"; // آی‌دی عددی کپی‌پذیر
        $userDetails .= "<code>🪪 اسمش: $firstName</code>\n";
        $userDetails .= "<code>✨ نام خانوادگیش: $lastName</code>\n";
        $userDetails .= "<code>👤 یوزرنیمش: $username</code>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'مشاهده جزئیات بیشتر', 'callback_data' => 'view_details']
                ]
            ]
        ];

        sendMessage($adminChatId, $userDetails, $keyboard);
    }
}

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
            ['text' => '🧩 آموزش اتصال', 'callback_data' => 'connection_guide']
        ]
    ]
];

if ($messageText == "/start") {
    sendMessage($chatId, $startText, $keyboard);
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
    file_get_contents($apiUrl . "sendMessage?" . http_build_query($data));
}
?>
