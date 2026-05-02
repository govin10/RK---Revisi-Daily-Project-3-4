<?php
session_start();
require_once '../includes/auth.php';
requireAdmin();

$db = getDB();

$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countStmt = $db->query("SELECT COUNT(*) FROM activity_logs");
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $db->query("
    SELECT a.*, u.nama, u.username 
    FROM activity_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$logs = $stmt->fetchAll();

$pageTitle = 'Log Aktivitas';
$activePage = 'logs';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-title">📋 Log Sistem / Audit Trail</div>
    <div class="card-subtitle">Merekam semua aktivitas pelacakan OSINT dan modifikasi data.</div>

    <div class="table-wrap mt-16">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User / Aktor</th>
                    <th>Aksi</th>
                    <th>Detail</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                <tr>
                    <td style="white-space:nowrap; font-size:12px;"><?= $log['created_at'] ?></td>
                    <td><strong><?= htmlspecialchars($log['nama'] ?? 'System') ?></strong> <br><span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($log['username'] ?? '-') ?></span></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($log['aksi']) ?></span></td>
                    <td><?= htmlspecialchars($log['detail']) ?></td>
                    <td style="font-size:12px; font-family:monospace;"><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($logs)): ?>
                <tr><td colspan="5" class="text-center">Belum ada log aktivitas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>" class="page-btn">«</a>
        <?php else: ?>
            <span class="page-btn disabled">«</span>
        <?php endif; ?>

        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>" class="page-btn">»</a>
        <?php else: ?>
            <span class="page-btn disabled">»</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
