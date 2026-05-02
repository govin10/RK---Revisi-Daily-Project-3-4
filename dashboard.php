<?php
session_start();
require_once 'includes/auth.php';
requireLogin();

$db = getDB();

// Get stats
$stmt = $db->query("SELECT COUNT(*) FROM alumni");
$totalAlumni = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan = 'selesai'");
$totalDilacak = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan = 'proses'");
$totalProses = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM activity_logs");
$totalAktivitas = $stmt->fetchColumn();

// Get recent tracked
$stmt = $db->query("SELECT * FROM alumni WHERE status_pelacakan = 'selesai' ORDER BY updated_at DESC LIMIT 5");
$recentAlumni = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card purple">
        <div class="stat-icon purple">👥</div>
        <div>
            <div class="stat-value"><?= number_format($totalAlumni) ?></div>
            <div class="stat-label">Total Alumni Terdaftar</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">✅</div>
        <div>
            <div class="stat-value"><?= number_format($totalDilacak) ?></div>
            <div class="stat-label">Alumni Berhasil Dilacak</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange">🔍</div>
        <div>
            <div class="stat-value"><?= number_format($totalProses) ?></div>
            <div class="stat-label">Sedang Proses Pelacakan</div>
        </div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-icon cyan">📊</div>
        <div>
            <div class="stat-value"><?= number_format($totalAktivitas) ?></div>
            <div class="stat-label">Log Aktivitas Sistem</div>
        </div>
    </div>
</div>

<div class="form-grid">
    <div class="card">
        <div class="card-title"> Radar Pelacakan Cepat</div>
        <div class="card-subtitle">Masukkan Nama dan NIM untuk memulai penelusuran OSINT.</div>
        
        <form action="/system-pelacak-alumni/alumni/lacak.php" method="GET">
            <div class="form-group mb-16">
                <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Ahmad Fauzi" required>
            </div>
            <div class="form-group mb-16">
                <label class="form-label">NIM Target</label>
                <input type="text" name="nim" class="form-control" placeholder="Contoh: 200110101001">
            </div>
            <button type="submit" class="btn btn-primary w-100" style="justify-content: center; padding: 12px; font-size: 15px;">
                Mulai Pelacakan Target 
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">⏱️ Terakhir Dilacak</div>
        <div class="card-subtitle">5 target terakhir yang profilnya berhasil diperbarui.</div>
        
        <?php if (empty($recentAlumni)): ?>
            <div class="empty-state">
                <span class="empty-icon">📭</span>
                <p>Belum ada data target yang berhasil dilacak.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Target</th>
                            <th>NIM</th>
                            <th>Waktu Pelacakan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentAlumni as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nama_lulusan']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nim']) ?></td>
                            <td><span style="font-size:12px;color:var(--text-muted);"><?= formatTanggal($row['tanggal_pelacakan']) ?></span></td>
                            <td>
                                <a href="/system-pelacak-alumni/alumni/detail.php?id=<?= $row['id'] ?>" class="btn btn-secondary btn-sm">Lihat Detail</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
