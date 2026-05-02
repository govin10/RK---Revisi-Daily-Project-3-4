<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    try {
        $db = getDB();
        $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, aksi, detail, ip_address) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], 'logout', 'User logout dari sistem', $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {}
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
