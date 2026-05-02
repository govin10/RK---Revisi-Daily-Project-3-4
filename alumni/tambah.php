<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lulusan'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $tahun_masuk = trim($_POST['tahun_masuk'] ?? '');
    $tahun_lulus = trim($_POST['tahun_lulus'] ?? '');
    $fakultas = trim($_POST['fakultas'] ?? '');
    $prodi = trim($_POST['program_studi'] ?? '');

    if (empty($nama)) {
        $error = "Nama lulusan wajib diisi.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO alumni (nama_lulusan, nim, tahun_masuk, tahun_lulus, fakultas, program_studi, status_pelacakan) VALUES (?, ?, ?, ?, ?, ?, 'belum')");
            $stmt->execute([$nama, $nim, $tahun_masuk, $tahun_lulus, $fakultas, $prodi]);
            
            $newId = $db->lastInsertId();
            logActivity('tambah_alumni', "Menambah data alumni baru: $nama ($nim)");
            $success = "Data alumni berhasil ditambahkan. Anda dapat mulai melacak target ini.";
            
            // Redirect to lacak page after success? Yes, makes it easier.
            header("Location: lacak.php?id=" . $newId);
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Tambah Alumni Baru';
$activePage = 'tambah';
require_once '../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-title">📝 Formulir Tambah Data</div>
    <div class="card-subtitle">Masukkan profil dasar alumni secara manual sebelum dilakukan pelacakan.</div>

    <?php if ($error): ?>
        <div class="alert alert-danger fade-in"><span>⚠️</span> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group mb-16">
            <label class="form-label">Nama Lengkap Lulusan <span class="req">*</span></label>
            <input type="text" name="nama_lulusan" class="form-control" required>
        </div>
        
        <div class="form-group mb-16">
            <label class="form-label">Nomor Induk Mahasiswa (NIM)</label>
            <input type="text" name="nim" class="form-control">
        </div>
        
        <div class="form-grid mb-16">
            <div class="form-group">
                <label class="form-label">Tahun Masuk</label>
                <input type="number" name="tahun_masuk" class="form-control" min="1980" max="<?= date('Y') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tahun Lulus</label>
                <input type="number" name="tahun_lulus" class="form-control" min="1980" max="<?= date('Y') ?>">
            </div>
        </div>

        <div class="form-group mb-16">
            <label class="form-label">Fakultas</label>
            <input type="text" name="fakultas" class="form-control">
        </div>

        <div class="form-group mb-24">
            <label class="form-label">Program Studi</label>
            <input type="text" name="program_studi" class="form-control">
        </div>

        <div class="d-flex gap-12">
            <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">Simpan & Lanjutkan ke Pelacakan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
