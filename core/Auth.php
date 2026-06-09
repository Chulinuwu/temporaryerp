<?php
/**
 * PEGASUS ERP - Session-based Authentication
 */

class Auth
{
    /**
     * Attempt to log in with username and password
     * Returns true on success, false on failure
     */
    public static function login($username, $password)
    {
        $db = Database::getInstance();

        $user = $db->fetch(
            "SELECT u.*, e.full_name, e.full_name_jp, e.position_title, e.division_id
             FROM users u
             LEFT JOIN employees e ON e.employee_id = u.employee_id
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = true LIMIT 1",
            [$username, $username]
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Store user data in session (exclude password hash)
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Update last login timestamp
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['user_id' => $user['user_id']]);

        return true;
    }

    /**
     * Log out the current user
     */
    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Check if a user is currently authenticated
     */
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user']);
    }

    /**
     * Get the currently authenticated user data
     * Returns the user array or null
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }
        return $_SESSION['user'];
    }

    /**
     * Get a specific field from the authenticated user
     */
    public static function userId()
    {
        $user = self::user();
        return $user ? ($user['user_id'] ?? null) : null;
    }

    /**
     * Check if the authenticated user has a specific role
     */
    public static function hasRole($role)
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        // Support role stored as a string column or in a related table
        if (isset($user['role'])) {
            // Single role field
            if ($user['role'] === $role) {
                return true;
            }
            // Comma-separated roles
            $roles = array_map('trim', explode(',', $user['role']));
            return in_array($role, $roles, true);
        }

        // If roles are loaded as an array
        if (isset($user['roles']) && is_array($user['roles'])) {
            return in_array($role, $user['roles'], true);
        }

        return false;
    }

    /**
     * Check if the current user can access a specific menu section.
     *
     * Role permissions:
     *   ADMIN          → All sections
     *   SALES_MANAGER  → dashboard, sales, purchasing, master(customers/suppliers/items)
     *   ACCOUNTING     → dashboard, accounting, ar, ap, cashflow, hr, payroll, expense
     *   STAFF          → dashboard, hr(own attendance/leave only)
     *
     * @param string $section  Section key: dashboard, sales, purchasing, inventory,
     *                         accounting, ar, ap, cashflow, hr, production, master, reports
     * @return bool
     */
    public static function canAccess(string $section): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $role = $user['role'] ?? 'STAFF';

        // ADMIN can access everything
        if ($role === 'ADMIN') {
            return true;
        }

        // Define permissions per role
        $permissions = [
            'SALES_MANAGER' => [
                'dashboard', 'sales', 'purchasing', 'master',
            ],
            'PURCHASE' => [
                'dashboard', 'purchasing', 'inventory', 'master',
            ],
            'MANAGER' => [
                'dashboard', 'sales', 'purchasing', 'inventory', 'master',
                'reports', 'cashflow', 'ar', 'ap',
            ],
            'ACCOUNTING' => [
                'dashboard', 'accounting', 'ar', 'ap', 'cashflow',
                'hr', 'payroll', 'expense', 'master',
            ],
            'STAFF' => [
                'dashboard', 'hr_self',
            ],
        ];

        $allowed = $permissions[$role] ?? ['dashboard'];

        // hr_self allows staff to see attendance/leave for themselves
        if ($section === 'hr_self' && in_array('hr_self', $allowed, true)) {
            return true;
        }

        // Sales & Consulting dept members get read access to sales modules
        // even if their role is STAFF.
        if (in_array($section, ['sales', 'master'], true) && self::isInSalesDept()) {
            return true;
        }

        return in_array($section, $allowed, true);
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && ($user['role'] ?? '') === 'ADMIN';
    }

    /**
     * Get current user role
     */
    public static function getRole(): string
    {
        $user = self::user();
        return $user['role'] ?? 'STAFF';
    }

    /**
     * Get current user's employee.position_level (fetches fresh if not in session).
     * Returns one of STAFF / ASSISTANT_MANAGER / MANAGER / DIRECTOR (or empty).
     */
    public static function positionLevel(): string
    {
        $user = self::user();
        if (!$user) return '';
        if (!empty($user['position_level'])) return strtoupper($user['position_level']);

        // lazy fetch if missing
        if (!empty($user['employee_id'])) {
            $db = Database::getInstance();
            $row = $db->fetch("SELECT position_level, department_id FROM employees WHERE employee_id = ?", [$user['employee_id']]);
            if ($row) {
                $_SESSION['user']['position_level'] = $row['position_level'];
                $_SESSION['user']['department_id']  = $row['department_id'];
                return strtoupper($row['position_level'] ?? '');
            }
        }
        return '';
    }

    /**
     * Numeric rank of position_level for comparison.
     */
    public static function levelRank(?string $lv): int
    {
        switch (strtoupper($lv ?? '')) {
            case 'DIRECTOR':          return 4;
            case 'MANAGER':           return 3;
            case 'ASSISTANT_MANAGER': return 2;
            case 'STAFF':             return 1;
            default:                  return 0;
        }
    }

    /** Current user is MANAGER or above (approves cost sheets & master changes). */
    public static function isManagerOrAbove(): bool
    {
        if (self::isAdmin()) return true;
        return self::levelRank(self::positionLevel()) >= self::levelRank('MANAGER');
    }

    /** Current user is DIRECTOR or above (approves quotations & purchase orders). */
    public static function isDirectorOrAbove(): bool
    {
        if (self::isAdmin()) return true;
        return self::levelRank(self::positionLevel()) >= self::levelRank('DIRECTOR');
    }

    /** Department id of the current user (from employees). */
    public static function departmentId(): ?int
    {
        $user = self::user();
        if (!$user) return null;
        if (isset($user['department_id']) && $user['department_id'] !== null) return (int)$user['department_id'];
        self::positionLevel(); // triggers lazy-fetch which also caches department_id
        return isset($_SESSION['user']['department_id']) ? (int)$_SESSION['user']['department_id'] : null;
    }

    /** True if the user belongs to the Sales & Consulting dept. */
    public static function isInSalesDept(): bool
    {
        $deptId = self::departmentId();
        if (!$deptId) return false;
        $db = Database::getInstance();
        $row = $db->fetch("SELECT department_code FROM departments WHERE department_id = ?", [$deptId]);
        return $row && in_array($row['department_code'], ['SALES_CONSULT','SALES'], true);
    }
}
