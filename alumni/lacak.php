<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

$db = getDB();
$id = $_GET['id'] ?? null;
$nama = $_GET['nama'] ?? '';
$nim = $_GET['nim'] ?? '';

$alumni = null;
$error = '';
$success = $_GET['success'] ?? '';

// Handle direct access from dashboard radar
if (!$id && $nama) {
    // Cari apakah sudah ada di database
    $stmt = $db->prepare("SELECT * FROM alumni WHERE nama_lulusan LIKE ? OR nim = ? LIMIT 1");
    $stmt->execute(["%$nama%", $nim]);
    $alumni = $stmt->fetch();

    if (!$alumni) {
        // Jika belum ada, kita insert sebagai target baru
        $stmt = $db->prepare("INSERT INTO alumni (nama_lulusan, nim, status_pelacakan, created_at) VALUES (?, ?, 'belum', NOW())");
        if ($stmt->execute([$nama, $nim])) {
            $id = $db->lastInsertId();
            $stmt = $db->prepare("SELECT * FROM alumni WHERE id = ?");
            $stmt->execute([$id]);
            $alumni = $stmt->fetch();
        }
    } else {
        $id = $alumni['id'];
    }
} elseif ($id) {
    $stmt = $db->prepare("SELECT * FROM alumni WHERE id = ?");
    $stmt->execute([$id]);
    $alumni = $stmt->fetch();
}

if (!$alumni) {
    die("Target tidak ditemukan. Silakan kembali ke Dashboard.");
}

// Handle Form Submission (OSINT Trigger)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'track') {
    // 1. Update basic target profile info
    $nama_baru = $_POST['nama_lulusan'] ?? $alumni['nama_lulusan'];
    $nim_baru = $_POST['nim'] ?? $alumni['nim'];
    $tahun_masuk = $_POST['tahun_masuk'] ?? null;
    $tahun_lulus = $_POST['tahun_lulus'] ?? null;
    $fakultas = $_POST['fakultas'] ?? null;
    $prodi = $_POST['program_studi'] ?? null;

    $stmt = $db->prepare("UPDATE alumni SET 
        nama_lulusan = ?, nim = ?, tahun_masuk = ?, tahun_lulus = ?, fakultas = ?, program_studi = ?,
        status_pelacakan = 'proses'
        WHERE id = ?");
    $stmt->execute([$nama_baru, $nim_baru, $tahun_masuk, $tahun_lulus, $fakultas, $prodi, $id]);

    logActivity('track_trigger', "Memicu pelacakan OSINT untuk target ID: $id ($nama_baru)");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_data') {
    $stmt = $db->prepare("UPDATE alumni SET 
        email = ?, no_hp = ?,
        linkedin_url = ?, instagram_url = ?, facebook_url = ?, tiktok_url = ?,
        tempat_bekerja = ?, alamat_bekerja = ?, posisi = ?, status_pekerjaan = ?,
        linkedin_kerja = ?, website_kerja = ?, instagram_kerja = ?, facebook_kerja = ?,
        pddikti_kampus = ?, pddikti_prodi = ?, pddikti_status = ?, pddikti_nim = ?, pddikti_url = ?,
        status_pelacakan = 'selesai', tanggal_pelacakan = NOW(), dilacak_oleh = ?
        WHERE id = ?");
    
    $stmt->execute([
        $_POST['email'], $_POST['no_hp'],
        $_POST['linkedin_url'], $_POST['instagram_url'], $_POST['facebook_url'], $_POST['tiktok_url'],
        $_POST['tempat_bekerja'], $_POST['alamat_bekerja'], $_POST['posisi'], $_POST['status_pekerjaan'] !== '' ? $_POST['status_pekerjaan'] : null,
        $_POST['linkedin_kerja'], $_POST['website_kerja'], $_POST['instagram_kerja'], $_POST['facebook_kerja'],
        $_POST['pddikti_kampus'], $_POST['pddikti_prodi'], $_POST['pddikti_status'], $_POST['pddikti_nim'], $_POST['pddikti_url'],
        $_SESSION['user_id'], $id
    ]);

    logActivity('save_target_data', "Menyimpan data hasil pelacakan target ID: $id");
    
    // Redirect ke halaman detail setelah sukses menyimpan
    header("Location: detail.php?id=$id&success=" . urlencode("Data hasil pelacakan berhasil disimpan ke pangkalan data!"));
    exit();
}

$pageTitle = 'Targeted OSINT Console';
$pageSubtitle = '» ' . htmlspecialchars($alumni['nama_lulusan'] ?? '');
$activePage = 'lacak';
require_once '../includes/header.php';
?>

<div class="osint-grid mb-24">
    <div class="card">
        <div class="card-title" style="color:var(--danger)">🔴 RADAR TARGET FORM</div>
        <div class="card-subtitle">Identitas primer pangkalan awal yang akan dilacak.</div>
        
        <form method="POST">
            <input type="hidden" name="action" value="track">
            <div class="form-grid">
                <div class="form-group mb-12">
                    <label class="form-label">Nama Target</label>
                    <input type="text" name="nama_lulusan" class="form-control" value="<?= htmlspecialchars($alumni['nama_lulusan'] ?? '') ?>" required>
                </div>
                <div class="form-group mb-12">
                    <label class="form-label">NIM (Opsional)</label>
                    <input type="text" name="nim" class="form-control" value="<?= htmlspecialchars($alumni['nim'] ?? '') ?>">
                </div>
                <div class="form-group mb-12">
                    <label class="form-label">Tahun Masuk</label>
                    <input type="number" name="tahun_masuk" class="form-control" value="<?= htmlspecialchars($alumni['tahun_masuk'] ?? '') ?>">
                </div>
                <div class="form-group mb-12">
                    <label class="form-label">Tahun Lulus</label>
                    <input type="number" name="tahun_lulus" class="form-control" value="<?= htmlspecialchars($alumni['tahun_lulus'] ?? '') ?>">
                </div>
                <div class="form-group mb-12">
                    <label class="form-label">Fakultas</label>
                    <input type="text" name="fakultas" class="form-control" value="<?= htmlspecialchars($alumni['fakultas'] ?? '') ?>">
                </div>
                <div class="form-group mb-12">
                    <label class="form-label">Program Studi</label>
                    <input type="text" name="program_studi" class="form-control" value="<?= htmlspecialchars($alumni['program_studi'] ?? '') ?>">
                </div>
            </div>
            
            <!-- Simulasi Tombol Trigger Otomatis Python Engine -->
            <button type="submit" class="btn btn-danger w-100 mt-16" style="justify-content: center; padding: 14px; font-size: 16px; letter-spacing: 1px; font-weight: 800; text-transform: uppercase;" <?= $alumni['status_pelacakan'] === 'proses' ? 'disabled' : '' ?>>
                <?= $alumni['status_pelacakan'] === 'proses' ? '⏳ Sedang Melacak...' : '📡 Lacak Menggunakan Sosial Media (OSINT Trigger)' ?>
            </button>
        </form>
    </div>

    <!-- Status Section -->
    <div class="card" style="display:flex; flex-direction:column;">
        <div class="card-title" style="color:var(--success)">🟢 STATUS PELACAKAN</div>
        <div class="card-subtitle">Kondisi intelijen target saat ini.</div>
        
        <div style="flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; background:var(--bg-surface); border-radius:var(--radius); border:1px solid var(--border); padding:20px;">
            <div style="font-size:48px; margin-bottom:10px;">
                <?= $alumni['status_pelacakan'] === 'selesai' ? '✅' : ($alumni['status_pelacakan'] === 'proses' ? '⏳' : '🔴') ?>
            </div>
            <h3 style="margin-bottom:5px;"><?= strtoupper($alumni['status_pelacakan']) ?></h3>
            <p style="text-align:center; color:var(--text-muted); font-size:13px;">
                <?= $alumni['status_pelacakan'] === 'selesai' 
                    ? 'Target berhasil diidentifikasi. Jejak digital telah direkam.' 
                    : 'Menunggu proses injeksi data target ke dalam mesin pencari otomatis.' ?>
            </p>
        </div>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success fade-in">
    <span>✅</span> <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['osint_candidates_' . $id])): ?>
<?php 
    $candidates = $_SESSION['osint_candidates_' . $id]; 
    unset($_SESSION['osint_candidates_' . $id]); 
?>
<div class="card fade-in mb-24" style="border:2px solid var(--primary);">
    <div class="card-title" style="color:var(--primary-light)">🔍 Hasil Penemuan OSINT (Pilih Kandidat)</div>
    <div class="card-subtitle">Mesin menemukan beberapa kandidat. Pilih salah satu untuk mengisi form di bawah secara otomatis.</div>
    
    <div style="display:flex; flex-direction:column; gap:15px; margin-top:20px;">
        <?php foreach($candidates as $idx => $cand): ?>
            <?php 
                $confColor = 'var(--text-muted)';
                $confIcon = '⚪';
                if ($cand['confidence'] === 'Kemungkinan kuat') { $confColor = 'var(--success)'; $confIcon = '🟢'; }
                elseif ($cand['confidence'] === 'Perlu verifikasi') { $confColor = 'var(--warning)'; $confIcon = '🟡'; }
                else { $confColor = 'var(--danger)'; $confIcon = '🔴'; }
            ?>
            <div style="background:var(--bg-dark); padding:15px; border-radius:10px; border-left:4px solid <?= $confColor ?>; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h4 style="margin-bottom:5px; color:var(--text-bright);"><?= htmlspecialchars($cand['name'] ?: 'Tanpa Nama') ?> <span style="font-size:12px; font-weight:normal; color:<?= $confColor ?>; margin-left:10px;"><?= $confIcon ?> <?= $cand['confidence'] ?> (Skor: <?= $cand['score'] ?>)</span></h4>
                    <div style="font-size:13px; color:var(--text-muted);">
                        Sumber: <strong><?= $cand['source'] ?></strong> | 
                        Pekerjaan: <?= htmlspecialchars($cand['tempat_bekerja'] ?: '-') ?> | 
                        Kampus: <?= htmlspecialchars($cand['pddikti_kampus'] ?: '-') ?>
                    </div>
                    <div style="margin-top:8px; display:flex; gap:12px;">
                        <?php if(!empty($cand['linkedin_url'])): ?>
                            <a href="<?= htmlspecialchars($cand['linkedin_url']) ?>" target="_blank" style="font-size:11px; color:var(--primary-light); text-decoration:none; background:rgba(124,58,237,0.1); padding:2px 8px; border-radius:4px; border:1px solid rgba(124,58,237,0.2);">LinkedIn ↗</a>
                        <?php endif; ?>
                        <?php if(!empty($cand['pddikti_url'])): ?>
                            <a href="<?= htmlspecialchars($cand['pddikti_url']) ?>" target="_blank" style="font-size:11px; color:#60A5FA; text-decoration:none; background:rgba(96,165,250,0.1); padding:2px 8px; border-radius:4px; border:1px solid rgba(96,165,250,0.2);">PDDikti ↗</a>
                        <?php endif; ?>
                        <?php if(!empty($cand['instagram_url'])): ?>
                            <a href="<?= htmlspecialchars($cand['instagram_url']) ?>" target="_blank" style="font-size:11px; color:#E1306C; text-decoration:none; background:rgba(225,48,108,0.1); padding:2px 8px; border-radius:4px; border:1px solid rgba(225,48,108,0.2);">Instagram ↗</a>
                        <?php endif; ?>
                        <?php if(!empty($cand['facebook_url'])): ?>
                            <a href="<?= htmlspecialchars($cand['facebook_url']) ?>" target="_blank" style="font-size:11px; color:#1877F2; text-decoration:none; background:rgba(24,119,242,0.1); padding:2px 8px; border-radius:4px; border:1px solid rgba(24,119,242,0.2);">Facebook ↗</a>
                        <?php endif; ?>
                        <?php if(!empty($cand['tiktok_url'])): ?>
                            <a href="<?= htmlspecialchars($cand['tiktok_url']) ?>" target="_blank" style="font-size:11px; color:#00f2ea; text-decoration:none; background:rgba(0,242,234,0.1); padding:2px 8px; border-radius:4px; border:1px solid rgba(0,242,234,0.2);">TikTok ↗</a>
                        <?php endif; ?>
                        <?php if(!empty($cand['website_kerja'])): ?>
                            <a href="<?= htmlspecialchars($cand['website_kerja']) ?>" target="_blank" style="font-size:11px; color:var(--success); text-decoration:none; background:rgba(16,185,129,0.1); padding:2px 8px; border-radius:4px; border:1px solid rgba(16,185,129,0.2);">Portfolio/Web ↗</a>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="fillForm(<?= htmlspecialchars(json_encode($cand), ENT_QUOTES, 'UTF-8') ?>)">Pilih Ini</button>
            </div>
        <?php endforeach; ?>
        <?php if(empty($candidates)): ?>
            <div style="text-align:center; padding:20px; color:var(--text-muted);">Tidak ada jejak digital yang ditemukan.</div>
        <?php endif; ?>
    </div>
</div>

<!-- OSINT Investigation Toolbox -->
<div class="card fade-in mb-24" style="background: rgba(255, 243, 205, 0.05); border: 1px solid rgba(255, 193, 7, 0.2);">
    <div class="card-title" style="color:#FFC107;">🛠️ Kotak Alat Investigasi (Manual Deep Research)</div>
    <div class="card-subtitle" style="color:#D1D5DB;">Gunakan perangkat di bawah jika mesin otomatis tidak menemukan data. Klik link untuk mencari jejak digital secara manual.</div>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-top:20px;">
        <!-- Academic Search -->
        <div style="background:rgba(255,255,255,0.02); padding:15px; border-radius:10px; border:1px solid rgba(255,255,255,0.1);">
            <div style="font-weight:bold; margin-bottom:10px; color:var(--primary-light);">🎓 Akademik & Publikasi</div>
            <ul style="list-style:none; padding:0; font-size:13px; line-height:2.2;">
                <li><a href="https://pddikti.kemdiktisaintek.go.id/search/<?= urlencode($alumni['nama_lulusan']) ?>" target="_blank" style="color:var(--text-bright); text-decoration:none;">🔍 PDDikti Explorer ↗</a></li>
                <li><a href="https://scholar.google.com/scholar?q=<?= urlencode($alumni['nama_lulusan']) ?>+UMM" target="_blank" style="color:var(--text-bright); text-decoration:none;">📚 Google Scholar ↗</a></li>
                <li><a href="https://sinta.kemdikbud.go.id/authors?q=<?= urlencode($alumni['nama_lulusan']) ?>" target="_blank" style="color:var(--text-bright); text-decoration:none;">📑 SINTA Authors ↗</a></li>
            </ul>
        </div>

        <!-- Social Media Search -->
        <div style="background:rgba(255,255,255,0.02); padding:15px; border-radius:10px; border:1px solid rgba(255,255,255,0.1);">
            <div style="font-weight:bold; margin-bottom:10px; color:#E1306C;">📱 Sosial Media</div>
            <ul style="list-style:none; padding:0; font-size:13px; line-height:2.2;">
                <li><a href="https://www.instagram.com/explore/search/keyword/?q=<?= urlencode($alumni['nama_lulusan']) ?>" target="_blank" style="color:var(--text-bright); text-decoration:none;">📸 Instagram Search ↗</a></li>
                <li><a href="https://www.facebook.com/search/top/?q=<?= urlencode($alumni['nama_lulusan']) ?>" target="_blank" style="color:var(--text-bright); text-decoration:none;">👥 Facebook People ↗</a></li>
                <li><a href="https://www.tiktok.com/search/user?q=<?= urlencode($alumni['nama_lulusan']) ?>" target="_blank" style="color:var(--text-bright); text-decoration:none;">🎵 TikTok Users ↗</a></li>
            </ul>
        </div>

        <!-- Professional Search -->
        <div style="background:rgba(255,255,255,0.02); padding:15px; border-radius:10px; border:1px solid rgba(255,255,255,0.1);">
            <div style="font-weight:bold; margin-bottom:10px; color:#0077B5;">💼 Karir & Profesi</div>
            <ul style="list-style:none; padding:0; font-size:13px; line-height:2.2;">
                <li><a href="https://www.linkedin.com/search/results/all/?keywords=<?= urlencode($alumni['nama_lulusan']) ?>%20UMM" target="_blank" style="color:var(--text-bright); text-decoration:none;">👔 LinkedIn (Name + UMM) ↗</a></li>
                <li><a href="https://glints.com/id/search?q=<?= urlencode($alumni['nama_lulusan']) ?>" target="_blank" style="color:var(--text-bright); text-decoration:none;">🏢 Glints Talent ↗</a></li>
                <li><a href="https://www.google.com/search?q=site:linkedin.com+\"<?= urlencode($alumni['nama_lulusan']) ?>\"" target="_blank" style="color:var(--text-bright); text-decoration:none;">🌍 Google Deep Search ↗</a></li>
            </ul>
        </div>
    </div>
</div>

<script>
function fillForm(data) {
    document.querySelector('[name="email"]').value = data.email || '';
    document.querySelector('[name="no_hp"]').value = data.no_hp || '';
    document.querySelector('[name="linkedin_url"]').value = data.linkedin_url || '';
    document.querySelector('[name="instagram_url"]').value = data.instagram_url || '';
    document.querySelector('[name="facebook_url"]').value = data.facebook_url || '';
    document.querySelector('[name="tiktok_url"]').value = data.tiktok_url || '';
    document.querySelector('[name="tempat_bekerja"]').value = data.tempat_bekerja || '';
    document.querySelector('[name="alamat_bekerja"]').value = data.alamat_bekerja || '';
    document.querySelector('[name="posisi"]').value = data.posisi || '';
    
    if(data.status_pekerjaan) {
        document.querySelector('[name="status_pekerjaan"]').value = data.status_pekerjaan;
    } else if (data.tempat_bekerja) {
        document.querySelector('[name="status_pekerjaan"]').value = 'Swasta'; 
    }
    
    document.querySelector('[name="linkedin_kerja"]').value = data.linkedin_kerja || '';
    document.querySelector('[name="website_kerja"]').value = data.website_kerja || '';
    document.querySelector('[name="instagram_kerja"]').value = data.instagram_kerja || '';
    document.querySelector('[name="facebook_kerja"]').value = data.facebook_kerja || '';
    
    document.querySelector('[name="pddikti_kampus"]').value = data.pddikti_kampus || '';
    document.querySelector('[name="pddikti_prodi"]').value = data.pddikti_prodi || '';
    document.querySelector('[name="pddikti_nim"]').value = data.pddikti_nim || '';
    document.querySelector('[name="pddikti_status"]').value = data.pddikti_status || '';
    document.querySelector('[name="pddikti_url"]').value = data.pddikti_url || '';
    
    alert("Form telah diisi secara otomatis menggunakan data dari kandidat terpilih. Silakan periksa kembali dan klik 'Simpan Hasil Pelacakan'.");
    document.getElementById('resultMatrix').scrollIntoView({behavior: "smooth"});
}
</script>
<?php endif; ?>

<!-- 8-PILLARS CORE RESULT MATRIX -->
<div class="card fade-in" id="resultMatrix">
    <div class="card-title" style="color:var(--primary-light)">💎 Selesai Dilacak (Target Ditemukan)</div>
    <div class="card-subtitle">Matriks Hasil Intelijen (The 8-Pillars Core) berdasarkan kriteria yang diminta. Silakan isi form di bawah jika data dikumpulkan secara manual/semi-otomatis.</div>
    
    <form method="POST">
        <input type="hidden" name="action" value="save_data">
        
        <div class="form-section">
            <div class="form-section-title"><span>📞</span> Pilar 1 & 2: Kontak Personal</div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Email Target (2)</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($alumni['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">No HP Target (3)</label>
                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($alumni['no_hp'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><span>🌐</span> Pilar 3: Alamat Sosial Media Pribadi (1)</div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">LinkedIn URL</label>
                    <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/..." value="<?= htmlspecialchars($alumni['linkedin_url'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram URL</label>
                    <input type="url" name="instagram_url" class="form-control" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($alumni['instagram_url'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Facebook URL</label>
                    <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($alumni['facebook_url'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">TikTok URL</label>
                    <input type="url" name="tiktok_url" class="form-control" placeholder="https://tiktok.com/@..." value="<?= htmlspecialchars($alumni['tiktok_url'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><span>🏢</span> Pilar 4, 5, 6, 7: Data Pekerjaan Terkini</div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Tempat Bekerja / Perusahaan (4)</label>
                    <input type="text" name="tempat_bekerja" class="form-control" value="<?= htmlspecialchars($alumni['tempat_bekerja'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Posisi / Jabatan (6)</label>
                    <input type="text" name="posisi" class="form-control" value="<?= htmlspecialchars($alumni['posisi'] ?? '') ?>">
                </div>
                <div class="form-group form-full">
                    <label class="form-label">Alamat Bekerja (5)</label>
                    <textarea name="alamat_bekerja" class="form-control" rows="2"><?= htmlspecialchars($alumni['alamat_bekerja'] ?? '') ?></textarea>
                </div>
                <div class="form-group form-full">
                    <label class="form-label">Kategori Status Pekerjaan (7)</label>
                    <select name="status_pekerjaan" class="form-control">
                        <option value="">-- Pilih --</option>
                        <option value="PNS" <?= $alumni['status_pekerjaan'] === 'PNS' ? 'selected' : '' ?>>PNS (Pegawai Negeri Sipil)</option>
                        <option value="Swasta" <?= $alumni['status_pekerjaan'] === 'Swasta' ? 'selected' : '' ?>>Swasta</option>
                        <option value="Wirausaha" <?= $alumni['status_pekerjaan'] === 'Wirausaha' ? 'selected' : '' ?>>Wirausaha / Entrepreneur</option>
                        <option value="Tidak Bekerja" <?= $alumni['status_pekerjaan'] === 'Tidak Bekerja' ? 'selected' : '' ?>>Tidak Bekerja</option>
                        <option value="Lainnya" <?= $alumni['status_pekerjaan'] === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><span>🔗</span> Pilar 8: Sosial Media & URL Tempat Bekerja</div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">LinkedIn Perusahaan</label>
                    <input type="url" name="linkedin_kerja" class="form-control" placeholder="https://linkedin.com/company/..." value="<?= htmlspecialchars($alumni['linkedin_kerja'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Website Perusahaan</label>
                    <input type="url" name="website_kerja" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($alumni['website_kerja'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram Perusahaan</label>
                    <input type="url" name="instagram_kerja" class="form-control" value="<?= htmlspecialchars($alumni['instagram_kerja'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Facebook Perusahaan</label>
                    <input type="url" name="facebook_kerja" class="form-control" value="<?= htmlspecialchars($alumni['facebook_kerja'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title" style="color:#60A5FA;"><span>🎓</span> Data Akademik (PDDikti)</div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kampus Terdaftar</label>
                    <input type="text" name="pddikti_kampus" class="form-control" value="<?= htmlspecialchars($alumni['pddikti_kampus'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Program Studi</label>
                    <input type="text" name="pddikti_prodi" class="form-control" value="<?= htmlspecialchars($alumni['pddikti_prodi'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">NIM (PDDikti)</label>
                    <input type="text" name="pddikti_nim" class="form-control" value="<?= htmlspecialchars($alumni['pddikti_nim'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Status Mahasiswa</label>
                    <input type="text" name="pddikti_status" class="form-control" value="<?= htmlspecialchars($alumni['pddikti_status'] ?? '') ?>">
                </div>
                <div class="form-group form-full">
                    <label class="form-label">Link Profil PDDikti</label>
                    <input type="url" name="pddikti_url" class="form-control" placeholder="https://pddikti.kemdiktisaintek.go.id/search/..." value="<?= htmlspecialchars($alumni['pddikti_url'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-group mt-16" style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-success" style="padding:12px 24px;">💾 Simpan Hasil Pelacakan ke Database</button>
            <a href="index.php" class="btn btn-secondary" style="padding:12px 24px;">Kembali ke Data Master</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php if ($alumni['status_pelacakan'] === 'proses'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let overlay = document.createElement('div');
    overlay.className = 'modal-overlay show';
    overlay.innerHTML = `
        <div class="modal text-center" style="max-width:400px; padding:40px;">
            <div class="pulse" style="font-size:70px; margin-bottom:20px;">📡</div>
            <h3 style="color:var(--primary-light); font-weight:800; font-size:20px;">OSINT Engine Aktif</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-top:10px; line-height:1.6;">
                Mesin Python sedang menelusuri jejak digital target di latar belakang.<br>
                Mohon tunggu beberapa detik...
            </p>
            <div class="progress-bar-wrap mt-16" style="background:var(--bg-dark); height:6px; border-radius:10px; overflow:hidden;">
                <div class="progress-bar-fill" style="width:100%; background:linear-gradient(90deg, var(--primary), var(--secondary)); animation: loading 2s infinite;"></div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    
    let style = document.createElement('style');
    style.innerHTML = '@keyframes loading { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }';
    document.head.appendChild(style);

    fetch('osint_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=<?= $id ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = 'lacak.php?id=<?= $id ?>&success=' + encodeURIComponent(data.message);
        } else {
            alert('Error: ' + data.message);
            overlay.remove();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan jaringan atau server gagal mengeksekusi Python.');
        overlay.remove();
    });
});
</script>
<?php endif; ?>
