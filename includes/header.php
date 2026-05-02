<?php
// includes/header.php - requires $pageTitle and $activePage variables
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Alumni UMM') ?> - Sistem Pelacak Alumni UMM</title>
  <meta name="description" content="Sistem Pelacak Alumni Universitas Muhammadiyah Malang">
  <link rel="stylesheet" href="/system-pelacak-alumni/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🎓</div>
    <div class="logo-text">
      <div class="logo-title">Pelacak Alumni</div>
      <div class="logo-sub">Universitas Muhammadiyah Malang</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Utama</div>
    <a href="/system-pelacak-alumni/dashboard.php" class="nav-item <?= ($activePage==='dashboard')?'active':'' ?>">
      <span class="nav-icon"></span> Dashboard
    </a>
    <a href="/system-pelacak-alumni/alumni/index.php" class="nav-item <?= ($activePage==='alumni')?'active':'' ?>">
      <span class="nav-icon"></span> Data Alumni
    </a>
    <a href="/system-pelacak-alumni/alumni/lacak.php" class="nav-item <?= ($activePage==='lacak')?'active':'' ?>">
      <span class="nav-icon"></span> Lacak Alumni
    </a>

    <div class="nav-section-label">Manajemen Data</div>
    <a href="/system-pelacak-alumni/alumni/tambah.php" class="nav-item <?= ($activePage==='tambah')?'active':'' ?>">
      <span class="nav-icon"></span> Tambah Alumni
    </a>
    <a href="/system-pelacak-alumni/alumni/import.php" class="nav-item <?= ($activePage==='import')?'active':'' ?>">
      <span class="nav-icon"></span> Import Data CSV
    </a>
    <a href="/system-pelacak-alumni/alumni/export.php" class="nav-item <?= ($activePage==='export')?'active':'' ?>">
      <span class="nav-icon"></span> Ekspor Data
    </a>

    <?php if ($user && $user['role'] === 'admin'): ?>
    <div class="nav-section-label">Admin</div>
    <a href="/system-pelacak-alumni/admin/users.php" class="nav-item <?= ($activePage==='users')?'active':'' ?>">
      <span class="nav-icon"></span> Kelola User
    </a>
    <a href="/system-pelacak-alumni/admin/logs.php" class="nav-item <?= ($activePage==='logs')?'active':'' ?>">
      <span class="nav-icon"></span> Log Aktivitas
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($user['nama']??'A',0,1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['nama']??'Admin') ?></div>
        <div class="user-role"><?= ucfirst($user['role']??'admin') ?></div>
      </div>
    </div>
    <a href="/system-pelacak-alumni/logout.php" class="btn btn-danger btn-sm w-100"> Logout</a>
  </div>
</aside>

<!-- MAIN CONTENT -->
<div class="main-content">
  <header class="topbar">
    <button class="btn btn-secondary btn-icon" id="sidebarToggle" onclick="toggleSidebar()" style="display:none">☰</button>
    <div class="topbar-title">
      <?= htmlspecialchars($pageTitle ?? '') ?>
      <?php if (isset($pageSubtitle)): ?>
        <span><?= htmlspecialchars($pageSubtitle) ?></span>
      <?php endif; ?>
    </div>
    <div class="topbar-actions">
      <span style="font-size:12px;color:var(--text-muted)">
        <?= date('d M Y, H:i') ?>
      </span>
    </div>
  </header>
  <div class="page-content fade-in">
