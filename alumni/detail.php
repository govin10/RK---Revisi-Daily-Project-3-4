<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM alumni WHERE id = ?");
$stmt->execute([$id]);
$alumni = $stmt->fetch();

if (!$alumni) {
    die("Data tidak ditemukan.");
}

$pageTitle = 'Detail Alumni';
$pageSubtitle = '» ' . htmlspecialchars($alumni['nama_lulusan'] ?? '');
$activePage = 'alumni';
require_once '../includes/header.php';

$success = $_GET['success'] ?? null;
?>

<?php if ($success): ?>
    <div class="alert alert-success fade-in mb-24">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<div class="card mb-24">
    <div class="detail-hero">
        <div class="detail-avatar"><?= strtoupper(substr($alumni['nama_lulusan'], 0, 1)) ?></div>
        <div>
            <div class="detail-name"><?= htmlspecialchars($alumni['nama_lulusan'] ?? '') ?> <?= $alumni['status_pelacakan'] === 'selesai' ? '✅' : '' ?></div>
            <div class="detail-nim">NIM: <?= htmlspecialchars($alumni['nim'] ?? '') ?> | Fakultas: <?= htmlspecialchars($alumni['fakultas'] ?? '') ?> | Tahun Lulus: <?= htmlspecialchars($alumni['tahun_lulus'] ?? '') ?></div>
            <div class="detail-meta">
                <?= statusBadge($alumni['status_pelacakan']) ?>
                <?= statusPekerjaanBadge($alumni['status_pekerjaan']) ?>
            </div>
        </div>
        <div style="margin-left:auto;">
            <a href="lacak.php?id=<?= $alumni['id'] ?>" class="btn btn-primary">🔍 Lacak / Edit Data</a>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-section">
            <div class="form-section-title"><span>👤</span> Data Akademik & Personal</div>
            <div class="info-row">
                <div class="info-label">Nama Lulusan</div>
                <div class="info-value"><strong><?= htmlspecialchars($alumni['nama_lulusan'] ?? '') ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">NIM</div>
                <div class="info-value"><?= htmlspecialchars($alumni['nim'] ?? '') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tahun Masuk / Lulus</div>
                <div class="info-value"><?= htmlspecialchars($alumni['tahun_masuk'] ?? '') ?> - <?= htmlspecialchars($alumni['tahun_lulus'] ?? '') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Fakultas / Prodi</div>
                <div class="info-value"><?= htmlspecialchars($alumni['fakultas'] ?? '') ?> / <?= htmlspecialchars($alumni['program_studi'] ?? '') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value"><?= htmlspecialchars($alumni['email'] ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">No HP</div>
                <div class="info-value"><?= htmlspecialchars($alumni['no_hp'] ?: '-') ?></div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title" style="color:#60A5FA;"><span>🎓</span> Data Akademik (PDDikti)</div>
            <div class="info-row">
                <div class="info-label">Kampus Terdaftar</div>
                <div class="info-value"><strong><?= htmlspecialchars($alumni['pddikti_kampus'] ?: '-') ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Program Studi</div>
                <div class="info-value"><?= htmlspecialchars($alumni['pddikti_prodi'] ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">NIM (PDDikti)</div>
                <div class="info-value"><?= htmlspecialchars($alumni['pddikti_nim'] ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Status Mahasiswa</div>
                <div class="info-value"><?= htmlspecialchars($alumni['pddikti_status'] ?: '-') ?></div>
            </div>
            <?php if(!empty($alumni['pddikti_url'])): ?>
            <div class="info-row">
                <div class="info-label">Link PDDikti</div>
                <div class="info-value"><a href="<?= htmlspecialchars($alumni['pddikti_url']) ?>" target="_blank" style="color:var(--primary); text-decoration:none;">Buka Profil PDDikti ↗</a></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-section">
            <div class="form-section-title"><span>🌐</span> Sosial Media Pribadi</div>
            <div class="social-links mt-16">
                <?php if($alumni['linkedin_url']): ?>
                    <a href="<?= htmlspecialchars($alumni['linkedin_url'] ?? '') ?>" target="_blank" class="social-link linkedin">LinkedIn</a>
                <?php endif; ?>
                <?php if($alumni['instagram_url']): ?>
                    <a href="<?= htmlspecialchars($alumni['instagram_url'] ?? '') ?>" target="_blank" class="social-link instagram">Instagram</a>
                <?php endif; ?>
                <?php if($alumni['facebook_url']): ?>
                    <a href="<?= htmlspecialchars($alumni['facebook_url'] ?? '') ?>" target="_blank" class="social-link facebook">Facebook</a>
                <?php endif; ?>
                <?php if($alumni['tiktok_url']): ?>
                    <a href="<?= htmlspecialchars($alumni['tiktok_url'] ?? '') ?>" target="_blank" class="social-link tiktok">TikTok</a>
                <?php endif; ?>
                
                <?php if(!$alumni['linkedin_url'] && !$alumni['instagram_url'] && !$alumni['facebook_url'] && !$alumni['tiktok_url']): ?>
                    <div style="color:var(--text-muted); font-size:13px; font-style:italic;">Belum ada data sosial media.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><span>🏢</span> Data Pekerjaan</div>
            <div class="info-row">
                <div class="info-label">Status Pekerjaan</div>
                <div class="info-value"><?= statusPekerjaanBadge($alumni['status_pekerjaan']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Posisi / Jabatan</div>
                <div class="info-value"><?= htmlspecialchars($alumni['posisi'] ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tempat Bekerja</div>
                <div class="info-value"><strong><?= htmlspecialchars($alumni['tempat_bekerja'] ?: '-') ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Alamat Bekerja</div>
                <div class="info-value"><?= nl2br(htmlspecialchars($alumni['alamat_bekerja'] ?: '-')) ?></div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><span>🔗</span> Kontak / Sosmed Tempat Kerja</div>
            <div class="social-links mt-16">
                <?php if($alumni['linkedin_kerja']): ?>
                    <a href="<?= htmlspecialchars($alumni['linkedin_kerja'] ?? '') ?>" target="_blank" class="social-link linkedin">LinkedIn Perusahaan</a>
                <?php endif; ?>
                <?php if($alumni['website_kerja']): ?>
                    <a href="<?= htmlspecialchars($alumni['website_kerja'] ?? '') ?>" target="_blank" class="social-link website">Website</a>
                <?php endif; ?>
                <?php if($alumni['instagram_kerja']): ?>
                    <a href="<?= htmlspecialchars($alumni['instagram_kerja'] ?? '') ?>" target="_blank" class="social-link instagram">Instagram Perusahaan</a>
                <?php endif; ?>
                <?php if($alumni['facebook_kerja']): ?>
                    <a href="<?= htmlspecialchars($alumni['facebook_kerja'] ?? '') ?>" target="_blank" class="social-link facebook">Facebook Perusahaan</a>
                <?php endif; ?>

                <?php if(!$alumni['linkedin_kerja'] && !$alumni['website_kerja'] && !$alumni['instagram_kerja'] && !$alumni['facebook_kerja']): ?>
                    <div style="color:var(--text-muted); font-size:13px; font-style:italic;">Belum ada data sosial media tempat kerja.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
