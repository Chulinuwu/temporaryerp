<?php
/**
 * PEGASUS ERP - Kintai Portal Layout
 * JobCan-inspired attendance/HR UI shell
 *
 * Variables:
 *   $pageTitle  - Page title
 *   $content    - Page body (already rendered)
 *   $activeMenu - Active top-level menu key (attendance|clock_edit|kosu|requests|staff_settings)
 *   $activeSub  - Active submenu key for requests (leave|overtime|early|clock_fix)
 */

$activeMenu = $activeMenu ?? '';
$activeSub  = $activeSub  ?? '';

$menus = [
    ['key' => 'attendance',     'label' => '出勤簿',      'icon' => '&#128197;', 'url' => '/kintai/attendance'],
    ['key' => 'clock_edit',     'label' => '打刻修正',    'icon' => '&#9201;',   'url' => '/kintai/clock-edit'],
    ['key' => 'kosu',           'label' => '工数管理',    'icon' => '&#128295;', 'url' => '/kintai/kosu', 'sub' => [
        ['key' => 'kosu_list',  'label' => '工数入力',    'url' => '/kintai/kosu'],
    ]],
    ['key' => 'requests',       'label' => '申請',        'icon' => '&#128220;', 'url' => '/kintai/requests', 'sub' => [
        ['key' => 'leave',      'label' => '休暇申請',        'url' => '/kintai/leave/new'],
        ['key' => 'overtime',   'label' => '残業申請',        'url' => '/kintai/overtime'],
        ['key' => 'early',      'label' => '早出残業申請',    'url' => '/kintai/overtime/early/new'],
        ['key' => 'clock_fix',  'label' => '打刻修正申請',    'url' => '/kintai/clock-edit'],
    ]],
    ['key' => 'staff_settings', 'label' => 'スタッフ設定', 'icon' => '&#9881;',   'url' => '/kintai/staff-settings'],
];

$user     = $_SESSION['user'] ?? ['full_name' => 'Admin Account', 'role' => 'Administrator'];
$staffCd  = $_SESSION['staff_code'] ?? '9999';
?><!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ja' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? '勤怠管理') ?> - PEGASUS 勤怠</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/kintai.css">
</head>
<body class="kt-body">

<!-- Top Header -->
<header class="kt-header">
    <div class="kt-logo">
        <span class="kt-logo-main">Pegasus</span>
        <span class="kt-logo-sub">勤怠管理</span>
    </div>
    <div class="kt-header-user">
        <a href="/kintai/staff-settings" class="u-name"><?= htmlspecialchars($user['full_name'] ?? 'Admin Account') ?>さん</a><br>
        <a href="/kintai/staff-settings">スタッフコード (<?= htmlspecialchars($staffCd) ?>)</a>
    </div>
</header>

<!-- Sidebar -->
<aside class="kt-sidebar" id="ktSidebar">
    <div class="kt-sidebar-close" onclick="document.getElementById('ktSidebar').classList.toggle('open')">&times;</div>
    <ul class="kt-sidebar-menu">
        <?php foreach ($menus as $menu): ?>
            <?php
                $hasSub   = !empty($menu['sub']);
                $isActive = $activeMenu === $menu['key'];
                $liClass  = trim(($hasSub ? 'has-sub ' : '') . ($isActive ? 'active open' : ''));
            ?>
            <li class="<?= $liClass ?>">
                <?php if ($hasSub): ?>
                    <div class="kt-menu-item" onclick="this.parentElement.classList.toggle('open')">
                        <span class="kt-menu-icon"><?= $menu['icon'] ?></span>
                        <span><?= htmlspecialchars($menu['label']) ?></span>
                    </div>
                    <ul class="kt-submenu">
                        <?php foreach ($menu['sub'] as $sub): ?>
                            <li class="<?= ($activeSub === $sub['key']) ? 'active' : '' ?>">
                                <a href="<?= htmlspecialchars($sub['url']) ?>"><?= htmlspecialchars($sub['label']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($menu['url']) ?>">
                        <span class="kt-menu-icon"><?= $menu['icon'] ?></span>
                        <span><?= htmlspecialchars($menu['label']) ?></span>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>

<!-- Main Content -->
<main class="kt-main">
    <?= $content ?? '' ?>
</main>

<!-- Language switcher -->
<div class="kt-lang">
    <select onchange="location.href='/lang/'+this.value">
        <option value="ja" <?= ($_SESSION['lang'] ?? 'ja') === 'ja' ? 'selected' : '' ?>>日本語</option>
        <option value="en" <?= ($_SESSION['lang'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
        <option value="th" <?= ($_SESSION['lang'] ?? '') === 'th' ? 'selected' : '' ?>>ไทย</option>
    </select>
</div>

<footer class="kt-footer">
    &copy; <?= date('Y') ?> PEGASUS ERP - Kintai Module
</footer>

</body>
</html>
