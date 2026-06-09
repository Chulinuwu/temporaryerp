<?php
/**
 * PEGASUS ERP — User & Password Management
 * Admin-only CRUD for users: create, list, reset password, activate/deactivate, change role.
 */
class UserController extends Controller
{
    private function requireAdmin(): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            exit;
        }
    }

    /** /admin/users — list all users */
    public function index()
    {
        $this->requireAdmin();

        $q = sanitize($_GET['q'] ?? '');
        $role = sanitize($_GET['role'] ?? '');
        $activeOnly = ($_GET['active'] ?? '1') === '1';

        $sql = "SELECT u.user_id, u.username, u.email, u.role, u.is_active, u.last_login, u.created_at,
                       u.employee_id, e.full_name AS employee_name
                FROM users u
                LEFT JOIN employees e ON e.employee_id = u.employee_id
                WHERE 1=1";
        $params = [];
        if ($activeOnly) $sql .= " AND u.is_active = TRUE";
        if ($q !== '') {
            $sql .= " AND (u.email ILIKE ? OR u.username ILIKE ? OR e.full_name ILIKE ?)";
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($role !== '') { $sql .= " AND u.role = ?"; $params[] = $role; }
        $sql .= " ORDER BY u.role, u.email";

        $users = $this->db->fetchAll($sql, $params) ?: [];

        $roles = $this->db->fetchAll(
            "SELECT role_code, role_name, role_name_jp, role_name_th FROM roles ORDER BY role_code"
        ) ?: [];

        $employees = $this->db->fetchAll(
            "SELECT employee_id, emp_code, full_name, email
             FROM employees
             WHERE is_deleted = FALSE
             ORDER BY full_name"
        ) ?: [];

        $this->render('admin/users', [
            'pageTitle' => __('menu_user_management'),
            'users' => $users,
            'roles' => $roles,
            'employees' => $employees,
            'filters' => ['q' => $q, 'role' => $role, 'active' => $activeOnly],
        ]);
    }

    /** Create new user */
    public function store()
    {
        $this->requireAdmin();

        $email    = trim(sanitize($_POST['email'] ?? ''));
        $username = trim(sanitize($_POST['username'] ?? '')) ?: $email;
        $role     = sanitize($_POST['role'] ?? 'STAFF');
        $empId    = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            flash('error', __('email_and_password_required'));
            $this->redirect('/admin/users');
            return;
        }
        if (strlen($password) < 6) {
            flash('error', __('password_too_short'));
            $this->redirect('/admin/users');
            return;
        }

        // Check duplicates
        $dup = $this->db->fetch(
            "SELECT user_id FROM users WHERE email = ? OR username = ?",
            [$email, $username]
        );
        if ($dup) {
            flash('error', __('email_or_username_exists'));
            $this->redirect('/admin/users');
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query(
            "INSERT INTO users (username, email, password_hash, role, employee_id, is_active)
             VALUES (?,?,?,?,?,TRUE)",
            [$username, $email, $hash, $role, $empId]
        );

        flash('success', __('user_created') . ': ' . $email);
        $this->redirect('/admin/users');
    }

    /** Reset (change) a single user's password */
    public function resetPassword($id)
    {
        $this->requireAdmin();

        $password = $_POST['password'] ?? '';
        if (strlen($password) < 6) {
            flash('error', __('password_too_short'));
            $this->redirect('/admin/users');
            return;
        }

        $u = $this->db->fetch("SELECT email FROM users WHERE user_id = ?", [$id]);
        if (!$u) {
            flash('error', __('user_not_found'));
            $this->redirect('/admin/users');
            return;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?", [$hash, $id]);
        flash('success', __('password_reset') . ': ' . $u['email']);
        $this->redirect('/admin/users');
    }

    /** Bulk reset all active users to a common password */
    public function bulkReset()
    {
        $this->requireAdmin();
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 6) {
            flash('error', __('password_too_short'));
            $this->redirect('/admin/users');
            return;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE is_active = TRUE", [$hash]);
        flash('success', __('bulk_password_reset_done'));
        $this->redirect('/admin/users');
    }

    /** Update role / active flag / linked employee */
    public function update($id)
    {
        $this->requireAdmin();
        $role   = sanitize($_POST['role'] ?? 'STAFF');
        $empId  = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
        $active = !empty($_POST['is_active']);

        // Never deactivate the last admin
        if (!$active) {
            $u = $this->db->fetch("SELECT role FROM users WHERE user_id = ?", [$id]);
            if ($u && $u['role'] === 'ADMIN') {
                $cnt = $this->db->fetch(
                    "SELECT COUNT(*) AS n FROM users WHERE role = 'ADMIN' AND is_active = TRUE AND user_id <> ?",
                    [$id]
                );
                if ((int)($cnt['n'] ?? 0) < 1) {
                    flash('error', __('cannot_disable_last_admin'));
                    $this->redirect('/admin/users');
                    return;
                }
            }
        }

        $this->db->query(
            "UPDATE users SET role = ?, employee_id = ?, is_active = ?, updated_at = NOW()
             WHERE user_id = ?",
            [$role, $empId, $active ? 't' : 'f', $id]
        );
        flash('success', __('user_updated'));
        $this->redirect('/admin/users');
    }

    /** Soft-delete / deactivate */
    public function delete($id)
    {
        $this->requireAdmin();
        $self = $this->getCurrentUser();
        if ((int)$id === (int)($self['user_id'] ?? 0)) {
            flash('error', __('cannot_delete_self'));
            $this->redirect('/admin/users');
            return;
        }

        // Protect last admin
        $u = $this->db->fetch("SELECT role FROM users WHERE user_id = ?", [$id]);
        if ($u && $u['role'] === 'ADMIN') {
            $cnt = $this->db->fetch(
                "SELECT COUNT(*) AS n FROM users WHERE role = 'ADMIN' AND is_active = TRUE AND user_id <> ?",
                [$id]
            );
            if ((int)($cnt['n'] ?? 0) < 1) {
                flash('error', __('cannot_disable_last_admin'));
                $this->redirect('/admin/users');
                return;
            }
        }

        $this->db->query("UPDATE users SET is_active = FALSE, updated_at = NOW() WHERE user_id = ?", [$id]);
        flash('success', __('user_deactivated'));
        $this->redirect('/admin/users');
    }
}
