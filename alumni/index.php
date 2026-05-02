<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

$db = getDB();

$db = getDB();

// Handle Delete
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];
    
    // Opsional: Cek nama untuk log
    $stmt = $db->prepare("SELECT nama_lulusan FROM alumni WHERE id = ?");
    $stmt->execute([$id_to_delete]);
    $target = $stmt->fetchColumn();

    if ($target) {
        $stmt = $db->prepare("DELETE FROM alumni WHERE id = ?");
        if ($stmt->execute([$id_to_delete])) {
            logActivity('hapus_alumni', "Menghapus data alumni: $target (ID: $id_to_delete)");
            // Redirect to avoid resubmission on reload
            header("Location: index.php?success=deleted");
            exit;
        }
    }
}

// Menangani pesan sukses
$success_msg = '';
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $success_msg = 'Data alumni berhasil dihapus dari sistem.';
}

// Pencarian dan Filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(nama_lulusan LIKE ? OR nim LIKE ? OR fakultas LIKE ? OR program_studi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where[] = "status_pelacakan = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countStmt = $db->prepare("SELECT COUNT(*) FROM alumni $whereClause");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$query = "SELECT * FROM alumni $whereClause ORDER BY nama_lulusan ASC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$alumniData = $stmt->fetchAll();

$pageTitle = 'Data Master Alumni';
$activePage = 'alumni';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-title">Database Alumni UMM</div>
    <div class="card-subtitle">Manajemen data master alumni. Total <?= number_format($totalRows) ?> data ditemukan.</div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success fade-in mb-20"><span>✅</span> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <form method="GET" class="filter-bar">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" placeholder="Cari nama, NIM, fakultas..." value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <select name="status" class="form-control" style="width: 200px;">
            <option value="">Semua Status</option>
            <option value="belum" <?= $status === 'belum' ? 'selected' : '' ?>>Belum Dilacak</option>
            <option value="proses" <?= $status === 'proses' ? 'selected' : '' ?>>Sedang Proses</option>
            <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai Dilacak</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter Data</button>
        <a href="bulk_track.php" class="btn btn-danger" style="background: linear-gradient(135deg, #EF4444, #B91C1C);"> Lacak Massal (OSINT)</a>
        <?php if($search || $status): ?>
            <a href="index.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama Lulusan</th>
                    <th>Fakultas / Prodi</th>
                    <th>Tahun Lulus</th>
                    <th>Status Pelacakan</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alumniData)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding: 30px;">
                        <span style="font-size:30px;opacity:0.5;display:block;margin-bottom:10px;">📭</span>
                        Tidak ada data alumni yang ditemukan.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($alumniData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nim'] ?? '') ?></td>
                        <td><strong><?= htmlspecialchars($row['nama_lulusan'] ?? '') ?></strong></td>
                        <td>
                            <div class="truncate" style="max-width:250px;">
                                <?= htmlspecialchars($row['fakultas'] ?? '') ?><br>
                                <span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($row['program_studi'] ?? '') ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['tahun_lulus'] ?? '') ?></td>
                        <td><?= statusBadge($row['status_pelacakan']) ?></td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="lacak.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" title="Mulai Pelacakan OSINT">🔍 Lacak</a>
                            <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary">Detail</a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Yakin ingin menghapus alumni <?= htmlspecialchars($row['nama_lulusan']) ?> permanen dari sistem?">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="page-btn">«</a>
        <?php else: ?>
            <span class="page-btn disabled">«</span>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $startPage + 4);
        if ($endPage - $startPage < 4) $startPage = max(1, $endPage - 4);
        
        for ($i = $startPage; $i <= $endPage; $i++): 
        ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="page-btn">»</a>
        <?php else: ?>
            <span class="page-btn disabled">»</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
