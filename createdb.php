<?php
require_once 'baseinfo.php';

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "دیتابیس با موفقیت ایجاد شد یا قبلاً وجود داشته است.<br>";
} else {
    echo "خطا در ایجاد دیتابیس: " . $conn->error . "<br>";
}

$conn->select_db($dbname);

$sql = "DROP TABLE IF EXISTS black_list";
if ($conn->query($sql) === TRUE) {
    echo "جدول black_list با موفقیت حذف شد.<br>";
} else {
    echo "خطا در حذف جدول black_list: " . $conn->error . "<br>";
}

$sql = "DROP TABLE IF EXISTS users";
if ($conn->query($sql) === TRUE) {
    echo "جدول users با موفقیت حذف شد.<br>";
} else {
    echo "خطا در حذف جدول users: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS black_list (
    chatid BIGINT PRIMARY KEY
)";
if ($conn->query($sql) === TRUE) {
    echo "جدول black_list با موفقیت ایجاد شد.<br>";
} else {
    echo "خطا در ایجاد جدول black_list: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    balance DECIMAL(10, 2) NOT NULL,
    number VARCHAR(255) NOT NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "جدول users با موفقیت ایجاد شد.<br>";
} else {
    echo "خطا در ایجاد جدول users: " . $conn->error . "<br>";
}

$conn->close();
?>
