<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $pageTitle ?? 'PEGASUS ERP' ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&family=Noto+Sans+JP:wght@300;400;500;700&family=Noto+Sans+Thai:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <!-- App CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">

    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
    <!-- ===== Authenticated Layout ===== -->

    <!-- Top Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Left Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['_flash'])): ?>
            <?php foreach ($_SESSION['_flash'] as $type => $message): ?>
                <div class="alert alert-<?= htmlspecialchars($type) ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['_flash']); ?>
        <?php endif; ?>

        <!-- Page Content -->
        <?= $content ?? '' ?>

        <!-- Footer -->
        <?php include __DIR__ . '/footer.php'; ?>
    </div>

<?php else: ?>
    <!-- ===== Guest Layout (Login, etc.) ===== -->

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['_flash'])): ?>
        <div style="position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:9999;width:400px;max-width:90vw;">
            <?php foreach ($_SESSION['_flash'] as $type => $message): ?>
                <div class="alert alert-<?= htmlspecialchars($type) ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['_flash']); ?>
    <?php endif; ?>

    <?= $content ?? '' ?>

<?php endif; ?>

<!-- App JS -->
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/components.js') ?>"></script>
</body>
</html>
