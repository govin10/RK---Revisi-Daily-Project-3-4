<?php
session_start();
require_once '../includes/auth.php';
requireAdmin(); // Hanya admin

$db = getDB();

$stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();

$pageTitle = 'Manajemen Pengguna';
$activePage = 'users';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-title">👥 Kelola Hak Akses Sistem</div>
    <div class="card-subtitle">Hanya Admin yang dapat mengelola akun.</div>

    <div class="table-wrap mt-16">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Terakhir Login</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td><?= htmlspecialchars($u['nama']) ?></td>
                    <td><?= htmlspecialchars($u['email'] ?: '-') ?></td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-secondary' ?>"><?= strtoupper($u['role']) ?></span></td>
                    <td><?= $u['last_login'] ? formatTanggal($u['last_login']) : 'Belum pernah' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
