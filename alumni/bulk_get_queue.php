<?php
session_start();
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    // Ambil batch target yang belum dilacak
    $stmt = $db->prepare("SELECT id, nama_lulusan, nim FROM alumni WHERE status_pelacakan = 'belum' ORDER BY id ASC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $targets = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $targets
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
