<?php
session_start();
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID target tidak ditemukan']);
    exit;
}

// 1. Ambil data alumni
$stmt = $db->prepare("SELECT * FROM alumni WHERE id = ?");
$stmt->execute([$id]);
$alumni = $stmt->fetch();

if (!$alumni) {
    echo json_encode(['status' => 'error', 'message' => 'Data alumni tidak valid']);
    exit;
}

// 2. Jalankan Mesin OSINT
$nama = $alumni['nama_lulusan'];
$nim = $alumni['nim'];
$fakultas = $alumni['fakultas'];
$tahun_lulus = $alumni['tahun_lulus'];

$scriptPath = "C:\\laragon\\www\\system-pelacak-alumni\\scripts\\osint_engine.py";
$command = "python " . escapeshellarg($scriptPath) . " " . 
           escapeshellarg($nama) . " " . 
           escapeshellarg($nim) . " " . 
           escapeshellarg($fakultas) . " " . 
           escapeshellarg($tahun_lulus);

$output = shell_exec($command);
$result = json_decode($output, true);

if (!$result || $result['status'] !== 'success' || empty($result['candidates'])) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mendapatkan data OSINT atau tidak ada kandidat']);
    exit;
}

// 3. Ambil kandidat TERBAIK (index 0 karena sudah disort by score)
$best = $result['candidates'][0];

// 4. Update Database Secara Otomatis
try {
    $updateStmt = $db->prepare("UPDATE alumni SET 
        email = ?, no_hp = ?,
        linkedin_url = ?, instagram_url = ?, facebook_url = ?, tiktok_url = ?,
        tempat_bekerja = ?, alamat_bekerja = ?, posisi = ?, status_pekerjaan = ?,
        website_kerja = ?,
        pddikti_kampus = ?, pddikti_prodi = ?, pddikti_status = ?, pddikti_nim = ?, pddikti_url = ?,
        status_pelacakan = 'selesai', tanggal_pelacakan = NOW(), dilacak_oleh = ?
        WHERE id = ?");

    $updateStmt->execute([
        $best['email'], $best['no_hp'],
        $best['linkedin_url'], $best['instagram_url'], $best['facebook_url'], $best['tiktok_url'],
        $best['tempat_bekerja'], $best['alamat_bekerja'], $best['posisi'], 
        ($best['status_pekerjaan'] !== '' ? $best['status_pekerjaan'] : null),
        $best['website_kerja'],
        $best['pddikti_kampus'], $best['pddikti_prodi'], $best['pddikti_status'], $best['pddikti_nim'], $best['pddikti_url'],
        $_SESSION['user_id'], $id
    ]);

    logActivity('bulk_auto_track', "Otomatisasi OSINT Berhasil untuk ID: $id (" . $best['source'] . ")");
    
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil di-update secara otomatis', 'source' => $best['source']]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal update database: ' . $e->getMessage()]);
}
