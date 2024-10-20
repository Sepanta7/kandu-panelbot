<?php
// وارد کردن اطلاعات اتصال به دیتابیس از فایل baseinfo.php
require_once 'baseinfo.php';

// ایجاد اتصال به دیتابیس
$conn = new mysqli($servername, $username, $password);

// بررسی اتصال
if ($conn->connect_error) {
    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

// ایجاد دیتابیس در صورتی که وجود ندارد
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "دیتابیس با موفقیت ایجاد شد یا قبلاً وجود داشته است.<br>";
} else {
    echo "خطا در ایجاد دیتابیس: " . $conn->error . "<br>";
}

// انتخاب دیتابیس
$conn->select_db($dbname);

// ایجاد جدول black_list
$sql = "CREATE TABLE IF NOT EXISTS black_list (
    chatid BIGINT PRIMARY KEY
)";
if ($conn->query($sql) === TRUE) {
    echo "جدول black_list با موفقیت ایجاد شد یا قبلاً وجود داشته است.<br>";
} else {
    echo "خطا در ایجاد جدول black_list: " . $conn->error . "<br>";
}

// ایجاد جدول users
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    balance DECIMAL(10, 2) NOT NULL,
    number VARCHAR(255) NOT NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "جدول users با موفقیت ایجاد شد یا قبلاً وجود داشته است.<br>";
} else {
    echo "خطا در ایجاد جدول users: " . $conn->error . "<br>";
}

// بستن اتصال به دیتابیس
$conn->close();
?>
