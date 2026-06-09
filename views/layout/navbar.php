<!-- Top Navigation Bar -->
<nav class="navbar">
    <!-- Left: Hamburger (mobile) + Logo -->
    <div class="d-flex items-center gap-2">
        <button class="navbar-hamburger" id="hamburgerBtn" aria-label="Toggle menu">&#9776;</button>
        <a href="/dashboard" class="navbar-logo navbar-logo-divided">
            <img src="/assets/PEGASUS_Logo_02.png" alt="PEGASUS">
            <span class="navbar-logo-divider"></span>
            <span class="navbar-logo-page"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
        </a>
    </div>

    <!-- Center: Date + Search -->
    <div class="navbar-center">
        <span class="navbar-date">
            <?php
                $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                $dow = $days[(int)date('w')];
                echo date('Y-m-d') . ' (' . $dow . ') | THB';
            ?>
        </span>
        <div class="navbar-search">
            <span class="search-icon">&#128269;</span>
            <input type="text" placeholder="<?= _e('search') ?>" id="globalSearch" autocomplete="off">
        </div>
    </div>

    <!-- Right: User + Logout -->
    <div class="d-flex items-center gap-3">
        <div class="navbar-user" onclick="document.getElementById('userDropdown').classList.toggle('active')">
            <div class="navbar-user-avatar">
                <?= strtoupper(substr($_SESSION['user']['full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="navbar-user-info">
                <span class="navbar-user-name"><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?></span>
                <span class="navbar-user-role"><?= htmlspecialchars($_SESSION['user']['role'] ?? 'Administrator') ?></span>
            </div>
            <span class="navbar-user-arrow">&#9662;</span>
        </div>
        <!-- User Dropdown -->
        <div class="navbar-dropdown" id="userDropdown">
            <a href="/hr/employees/<?= $_SESSION['employee_id'] ?? '' ?>" class="navbar-dropdown-item">
                <span>&#128100;</span> <?= _e('menu_employees') ?>
            </a>
            <div class="navbar-dropdown-divider"></div>
            <a href="/logout" class="navbar-dropdown-item navbar-dropdown-logout">
                <span>&#9211;</span> <?= _e('logout') ?>
            </a>
        </div>
    </div>
</nav>
<script>
// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    var dd = document.getElementById('userDropdown');
    if (dd && !e.target.closest('.navbar-user') && !e.target.closest('.navbar-dropdown')) {
        dd.classList.remove('active');
    }
});
</script>
