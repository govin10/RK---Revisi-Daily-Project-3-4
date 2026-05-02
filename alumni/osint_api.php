<?php
session_start();
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID Target tidak ditemukan.']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM alumni WHERE id = ?");
$stmt->execute([$id]);
$alumni = $stmt->fetch();

if (!$alumni) {
    echo json_encode(['status' => 'error', 'message' => 'Data target tidak ditemukan di database.']);
    exit;
}

// Persiapkan argumen untuk Python
$scriptPath = realpath(__DIR__ . '/../scripts/osint_engine.py');
$nama = escapeshellarg($alumni['nama_lulusan']);
$nim = escapeshellarg($alumni['nim'] ?? '');
$fakultas = escapeshellarg($alumni['fakultas'] ?? '');
$tahun = escapeshellarg($alumni['tahun_lulus'] ?? '');

// Eksekusi Python
$command = "python \"$scriptPath\" $nama $nim $fakultas $tahun 2>&1";
$output = shell_exec($command);

if (!$output) {
    echo json_encode(['status' => 'error', 'message' => 'Python engine gagal dieksekusi atau tidak mengembalikan hasil. Pastikan Python terinstall.']);
    exit;
}

$result = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($result['status'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Output dari mesin tidak valid (Bukan JSON).',
        'raw_output' => $output
    ]);
    exit;
}

if ($result['status'] === 'success') {
    $candidates = $result['candidates'];
    $_SESSION['osint_candidates_' . $id] = $candidates;
    
    // Memperbarui status agar tidak terjadi infinite loop (refresh terus menerus)
    $stmt = $db->prepare("UPDATE alumni SET status_pelacakan = 'selesai' WHERE id = ?");
    $stmt->execute([$id]);
    // We send the candidates list back to the frontend for the user to review and select.
    
    logActivity('osint_auto_track', "Berhasil menjalankan mesin OSINT untuk ID: $id. Menemukan " . count($candidates) . " kandidat.");

    echo json_encode(['status' => 'success', 'candidates' => $candidates, 'message' => 'Kandidat berhasil ditemukan.']);
} else {
    echo json_encode(['status' => 'error', 'message' => $result['message'] ?? 'Kesalahan internal mesin OSINT.']);
}
