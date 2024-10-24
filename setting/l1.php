<?php
require_once 'baseinfo.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("اتصال به دیتابیس با خطا مواجه شد: " . $conn->connect_error);
}

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

function blockUser($userId, $conn) {
    $sql = "INSERT IGNORE INTO black_list (chatid) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function unblockUser($userId, $conn) {
    $sql = "DELETE FROM black_list WHERE chatid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>
