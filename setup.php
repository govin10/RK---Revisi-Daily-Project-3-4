<?php
/**
 * Setup Database - Alumni UMM Tracker
 * Jalankan sekali: http://localhost/system-pelacak-alumni/setup.php
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Buat database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `alumni_umm` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `alumni_umm`");

    // Tabel users
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nama` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100),
        `role` ENUM('admin','operator') DEFAULT 'admin',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabel alumni (data utama)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `alumni` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `nama_lulusan` VARCHAR(255) NOT NULL,
        `nim` VARCHAR(30),
        `tahun_masuk` YEAR,
        `tanggal_lulus` DATE,
        `tahun_lulus` YEAR,
        `fakultas` VARCHAR(150),
        `program_studi` VARCHAR(150),
        -- Kontak pribadi
        `email` VARCHAR(255),
        `no_hp` VARCHAR(25),
        -- Sosial media pribadi
        `linkedin_url` VARCHAR(500),
        `instagram_url` VARCHAR(500),
        `facebook_url` VARCHAR(500),
        `tiktok_url` VARCHAR(500),
        -- Data pekerjaan
        `tempat_bekerja` VARCHAR(255),
        `alamat_bekerja` TEXT,
        `posisi` VARCHAR(200),
        `status_pekerjaan` ENUM('PNS','Swasta','Wirausaha','Tidak Bekerja','Lainnya') DEFAULT NULL,
        -- Sosial media tempat bekerja
        `linkedin_kerja` VARCHAR(500),
        `instagram_kerja` VARCHAR(500),
        `facebook_kerja` VARCHAR(500),
        `website_kerja` VARCHAR(500),
        -- PDDikti
        `pddikti_kampus` VARCHAR(255),
        `pddikti_prodi` VARCHAR(255),
        `pddikti_status` VARCHAR(50),
        `pddikti_nim` VARCHAR(50),
        `pddikti_url` VARCHAR(500),
        -- Meta
        `status_pelacakan` ENUM('belum','proses','selesai') DEFAULT 'belum',
        `catatan` TEXT,
        `dilacak_oleh` INT,
        `tanggal_pelacakan` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_nim` (`nim`),
        INDEX `idx_nama` (`nama_lulusan`),
        INDEX `idx_tahun_lulus` (`tahun_lulus`),
        INDEX `idx_fakultas` (`fakultas`),
        INDEX `idx_status` (`status_pelacakan`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabel import logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `import_logs` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `filename` VARCHAR(255),
        `total_rows` INT DEFAULT 0,
        `imported_rows` INT DEFAULT 0,
        `skipped_rows` INT DEFAULT 0,
        `imported_by` INT,
        `imported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabel aktivitas log
    $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT,
        `aksi` VARCHAR(100),
        `detail` TEXT,
        `ip_address` VARCHAR(45),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insert default admin
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO `users` (`username`, `password`, `nama`, `email`, `role`) 
                VALUES ('admin', '$adminPassword', 'Administrator', 'admin@umm.ac.id', 'admin')");

    // Insert sample alumni data (dari spreadsheet UMM)
    $pdo->exec("INSERT IGNORE INTO `alumni` (`nama_lulusan`, `nim`, `tahun_masuk`, `tanggal_lulus`, `tahun_lulus`, `fakultas`, `program_studi`, `status_pelacakan`) VALUES
        ('Ahmad Fauzi', '200110101001', 2000, '2004-08-15', 2004, 'Teknik', 'Teknik Informatika', 'belum'),
        ('Siti Rahayu', '200210201001', 2002, '2006-09-10', 2006, 'Ekonomi & Bisnis', 'Manajemen', 'belum'),
        ('Budi Santoso', '200110101002', 2001, '2005-07-20', 2005, 'Teknik', 'Teknik Sipil', 'belum'),
        ('Dewi Anggraini', '200310301001', 2003, '2007-08-25', 2007, 'FISIP', 'Ilmu Komunikasi', 'belum'),
        ('Rizki Pratama', '200510501001', 2005, '2009-09-15', 2009, 'Hukum', 'Ilmu Hukum', 'belum'),
        ('Nur Hidayah', '200210201002', 2002, '2006-08-30', 2006, 'Ekonomi & Bisnis', 'Akuntansi', 'belum'),
        ('Muhammad Yusuf', '200110101003', 2001, '2005-09-05', 2005, 'Teknik', 'Teknik Elektro', 'belum'),
        ('Rina Wulandari', '200410401001', 2004, '2008-08-20', 2008, 'Psikologi', 'Psikologi', 'belum'),
        ('Agus Setiawan', '200310301002', 2003, '2007-09-10', 2007, 'FISIP', 'Administrasi Publik', 'belum'),
        ('Fatimah Zahra', '200610601001', 2006, '2010-08-15', 2010, 'Kedokteran', 'Pendidikan Dokter', 'belum')
    ");

    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Berhasil - Alumni UMM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "Segoe UI", sans-serif; 
            background: linear-gradient(135deg, #0a0a14 0%, #1a0a2e 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            backdrop-filter: blur(20px);
            text-align: center;
        }
        .icon { font-size: 64px; margin-bottom: 20px; }
        h1 { color: #10B981; font-size: 28px; margin-bottom: 10px; }
        p { color: #94A3B8; line-height: 1.6; margin-bottom: 15px; }
        .info { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); 
                border-radius: 10px; padding: 15px; margin: 20px 0; text-align: left; }
        .info p { color: #6EE7B7; margin: 5px 0; font-size: 14px; }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #7C3AED, #4F46E5);
            color: white; padding: 14px 30px; border-radius: 10px;
            text-decoration: none; font-weight: 600; margin-top: 10px;
            transition: all 0.3s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(124,58,237,0.4); }
        strong { color: #F1F5F9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>Setup Berhasil!</h1>
        <p>Database <strong>alumni_umm</strong> telah berhasil dibuat beserta semua tabel yang diperlukan.</p>
        <div class="info">
            <p>🔑 <strong>Username:</strong> admin</p>
            <p>🔐 <strong>Password:</strong> admin123</p>
            <p>🗄️ <strong>Database:</strong> alumni_umm</p>
        </div>
        <p style="color: #EF4444; font-size: 13px;">⚠️ Hapus file setup.php setelah selesai untuk keamanan!</p>
        <a href="login.php" class="btn">🚀 Mulai Login</a>
    </div>
</body>
</html>';

} catch (PDOException $e) {
    echo '<div style="background:#1a0a0a;color:#EF4444;padding:30px;font-family:monospace;min-height:100vh;">
        <h2>❌ Setup Gagal</h2>
        <p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Pastikan MySQL server Laragon sudah berjalan!</p>
    </div>';
}
