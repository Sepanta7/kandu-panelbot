<?php
$botToken = "توکن ربات شما";
$apiUrl = "https://api.telegram.org/bot$botToken/";

$adminChatId = "چت ایدی ادمین"; 

$servername = "localhost";
$username = "یوزرنیم دیتابیس";
$password = "رمز دیتابیس";
$dbname = "نام دیتابیس";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$update = json_decode(file_get_contents('php:
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
        $userDetails .= "<blockquote>";
        $userDetails .= "🆔 آیدی عددیش: <code>$chatId</code>\n";
        $userDetails .= "🪪 اسمش: <code>$firstName</code>\n";
        $userDetails .= "✨ نام خانوادگیش: <code>$lastName</code>\n";
        $userDetails .= "👤 یوزرنیمش: <code>$username</code>";
        $userDetails .= "</blockquote>";

        sendMessage($adminChatId, $userDetails);

        
        $startText = "سلام! خوش اومدی به ربات ما. لطفاً یکی از گزینه‌های زیر رو انتخاب کن:";
        $messageId = sendMessage($chatId, $startText, $keyboard);
        editMessageText($chatId, $messageId, $startText, $keyboard); 
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
            ['text' => '🧩 آموزش اتصال', 'callback_data' => 'connection_guide'],
            ['text' => '⚙️ مدیریت', 'callback_data' => 'manage_panel'],
        ]
    ]
];

if ($messageText == "/start") {
    sendMessage($chatId, $startText, $keyboard);
}

if (isset($update['callback_query'])) {
    $callbackData = $update['callback_query']['data'];
    $chatId = $update['callback_query']['message']['chat']['id'];

    if ($callbackData == "manage_panel") {
        
        $manageText = "مدیر عزیز اینجا پنل مدیریت رباته، تو اینجا میتونی بر اساس نیاز هات ربات رو تنظیم کنی. راستی اگه از ربات درآمد داری خوشحال میشم عضو کانال توسعه دهندم بشی😊";

        $keyboard_manage = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 آمار ربات', 'callback_data' => 'show_stats'],
                    ['text' => '🛠️ تنظیمات ربات', 'callback_data' => 'robot_settings']
                ],
                [
                    ['text' => '⚙️ مدیریت', 'callback_data' => 'manage_panel']
                ]
            ]
        ];

        sendMessage($chatId, $manageText, $keyboard_manage);
    }

    if ($callbackData == "show_stats") {
        
        $sql_users_count = "SELECT COUNT(*) as total_users FROM users";
        $result_users = $conn->query($sql_users_count);
        $row_users = $result_users->fetch_assoc();
        $totalUsers = $row_users['total_users'];

        $statsText = "<b>این جا آمار رباتت رو زدم می‌تونی ببینی:</b>\n\n";
        $statsText .= "<b>تعداد کاربران:</b> <code>$totalUsers نفر</code>\n";

        $keyboard_stats = [
            'inline_keyboard' => [
                [
                    ['text' => 'تعداد کاربران', 'callback_data' => 'no_action']
                ]
            ]
        ];

        sendMessage($chatId, $statsText, $keyboard_stats);
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
    return $responseData['result']['message_id']; 
}

function editMessageText($chatId, $messageId, $text, $keyboard = null)
{
    global $apiUrl;
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    file_get_contents($apiUrl . "editMessageText?" . http_build_query($data));
}
?>
