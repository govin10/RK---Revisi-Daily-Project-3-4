<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $db = getDB();
    
    // Status filter
    $status = $_GET['status'] ?? '';
    $where = "";
    $params = [];
    if ($status) {
        $where = "WHERE status_pelacakan = ?";
        $params[] = $status;
    }
    
    $stmt = $db->prepare("SELECT 
        nama_lulusan, nim, tahun_masuk, tahun_lulus, fakultas, program_studi,
        email, no_hp, linkedin_url, instagram_url, facebook_url, tiktok_url,
        tempat_bekerja, alamat_bekerja, posisi, status_pekerjaan,
        linkedin_kerja, website_kerja, instagram_kerja, facebook_kerja,
        status_pelacakan, tanggal_pelacakan
    FROM alumni $where ORDER BY nama_lulusan ASC");
    $stmt->execute($params);
    
    $filename = "Export_Alumni_OSINT_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Output UTF-8 BOM for Excel compatibility
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Header
    fputcsv($output, [
        'Nama Lulusan', 'NIM', 'Tahun Masuk', 'Tahun Lulus', 'Fakultas', 'Program Studi',
        'Email', 'No HP', 'LinkedIn Pribadi', 'Instagram Pribadi', 'Facebook Pribadi', 'TikTok',
        'Tempat Bekerja', 'Alamat Bekerja', 'Posisi', 'Status Pekerjaan',
        'LinkedIn Tempat Kerja', 'Website Tempat Kerja', 'Instagram Tempat Kerja', 'Facebook Tempat Kerja',
        'Status Pelacakan', 'Tanggal Dilacak'
    ]);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    logActivity('export_csv', "Mengekspor data alumni ke CSV. Filter status: " . ($status ?: 'Semua'));
    exit;
}

$pageTitle = 'Export Data';
$activePage = 'export';
require_once '../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-title">📤 Export Data ke CSV (Excel)</div>
    <div class="card-subtitle">Unduh database beserta semua pilar hasil pelacakan OSINT.</div>

    <form method="GET" action="">
        <input type="hidden" name="action" value="download">
        
        <div class="form-group mb-24 mt-16">
            <label class="form-label">Filter Data yang Diekspor</label>
            <select name="status" class="form-control">
                <option value="">Semua Data (Termasuk yang belum dilacak)</option>
                <option value="selesai">Hanya Target yang BERHASIL DILACAK (8-Pilar Data Lengkap)</option>
                <option value="belum">Hanya Target yang BELUM dilacak</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success w-100" style="justify-content: center; padding: 12px; font-size: 15px;">
            Unduh Laporan CSV 📊
        </button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
