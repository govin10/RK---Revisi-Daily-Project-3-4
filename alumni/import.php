<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Terjadi kesalahan saat mengunggah file.";
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'csv') {
            $error = "Hanya file CSV yang diperbolehkan. Silakan convert Excel Anda ke CSV (Comma delimited).";
        } else {
            $handle = fopen($file['tmp_name'], "r");
            if ($handle !== FALSE) {
                // Skip header row
                fgetcsv($handle, 10000, ",");
                
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare("INSERT INTO alumni (nama_lulusan, nim, tahun_masuk, tahun_lulus, fakultas, program_studi) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    $imported = 0;
                    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        $nama = trim($data[0] ?? '');
                        if (empty($nama)) continue;

                        // Ekstrak tahun (4 digit) dari data mentah untuk menghindari error YEAR
                        $tahun_masuk_raw = trim($data[2] ?? '');
                        $tahun_lulus_raw = trim($data[3] ?? '');
                        
                        $tahun_masuk = null;
                        if (preg_match('/\b(19|20)\d{2}\b/', $tahun_masuk_raw, $matches)) {
                            $tahun_masuk = $matches[0];
                        }
                        
                        $tahun_lulus = null;
                        if (preg_match('/\b(19|20)\d{2}\b/', $tahun_lulus_raw, $matches)) {
                            $tahun_lulus = $matches[0];
                        }
                        
                        $stmt->execute([
                            $nama,
                            trim($data[1] ?? ''),
                            $tahun_masuk,
                            $tahun_lulus,
                            trim($data[4] ?? ''),
                            trim($data[5] ?? '')
                        ]);
                        $imported++;
                    }
                    
                    $db->commit();
                    logActivity('import_csv', "Import CSV berhasil. $imported baris diimpor.");
                    $success = "$imported data alumni berhasil diimpor ke sistem!";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = "Gagal mengimpor data: " . $e->getMessage();
                }
                fclose($handle);
            }
        }
    }
}

$pageTitle = 'Import Data CSV';
$activePage = 'import';
require_once '../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-title">📥 Import Massal Database</div>
    <div class="card-subtitle">Upload file CSV (Comma delimited) yang berisi daftar alumni dari Excel Anda.</div>

    <?php if ($error): ?>
        <div class="alert alert-danger fade-in"><span>⚠️</span> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success fade-in"><span>✅</span> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="alert alert-info mb-20">
        <strong>Format Kolom CSV (Berurutan):</strong><br>
        1. Nama Lulusan<br>
        2. NIM<br>
        3. Tahun Masuk<br>
        4. Tahun Lulus<br>
        5. Fakultas<br>
        6. Program Studi<br><br>
        <em>*Baris pertama (header) akan otomatis diabaikan. Pastikan menggunakan pemisah koma (,).</em>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group mb-24">
            <label class="form-label">Pilih File CSV <span class="req">*</span></label>
            <div style="border: 2px dashed var(--border); padding: 40px; text-align: center; border-radius: var(--radius); background: var(--bg-surface);">
                <span style="font-size: 32px; display: block; margin-bottom: 10px;">📄</span>
                <input type="file" name="csv_file" accept=".csv" required style="max-width: 100%;">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100" style="justify-content: center; padding: 12px; font-size: 15px;">
            Mulai Import Data
        </button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
