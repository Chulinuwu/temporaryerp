<?php
/**
 * PEGASUS ERP — Audit Log Viewer (#1)
 * Admin-only menu showing all changes recorded by audit triggers.
 */

class AuditLogController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            return;
        }

        $filters = [
            'table'     => sanitize($_GET['table'] ?? ''),
            'operation' => sanitize($_GET['operation'] ?? ''),
            'user'      => sanitize($_GET['user'] ?? ''),
            'date_from' => sanitize($_GET['date_from'] ?? ''),
            'date_to'   => sanitize($_GET['date_to'] ?? ''),
            'q'         => sanitize($_GET['q'] ?? ''),
        ];

        $sql = "SELECT al.*, u.username AS changed_by_name, u.email AS changed_by_email,
                       e.full_name AS changed_by_full_name
                FROM audit_logs al
                LEFT JOIN users u ON u.user_id = al.changed_by
                LEFT JOIN employees e ON e.employee_id = u.employee_id
                WHERE 1=1";
        $params = [];

        if ($filters['table'] !== '')     { $sql .= " AND al.table_name = ?"; $params[] = $filters['table']; }
        if ($filters['operation'] !== '') { $sql .= " AND al.operation = ?"; $params[] = $filters['operation']; }
        if ($filters['user'] !== '')      { $sql .= " AND al.changed_by = ?"; $params[] = $filters['user']; }
        if ($filters['date_from'] !== '') { $sql .= " AND al.changed_at >= ?"; $params[] = $filters['date_from']; }
        if ($filters['date_to'] !== '')   { $sql .= " AND al.changed_at <= ?::date + INTERVAL '1 day'"; $params[] = $filters['date_to']; }
        if ($filters['q'] !== '') {
            $sql .= " AND (al.table_name ILIKE ? OR al.changed_fields::text ILIKE ?
                         OR al.old_values::text ILIKE ? OR al.new_values::text ILIKE ?)";
            $like = '%' . $filters['q'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $sql .= " ORDER BY al.changed_at DESC LIMIT 500";
        $logs = $this->db->fetchAll($sql, $params);

        // Distinct tables for filter
        $tables = $this->db->fetchAll(
            "SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name"
        );

        // Users for filter
        $users = $this->db->fetchAll(
            "SELECT u.user_id, u.username, COALESCE(e.full_name, u.username) AS display_name
             FROM users u
             LEFT JOIN employees e ON e.employee_id = u.employee_id
             WHERE u.user_id IN (SELECT DISTINCT changed_by FROM audit_logs WHERE changed_by IS NOT NULL)
             ORDER BY display_name"
        );

        $this->render('audit/logs', [
            'pageTitle' => __('menu_audit_log'),
            'logs'      => $logs ?: [],
            'tables'    => $tables ?: [],
            'users'     => $users ?: [],
            'filters'   => $filters,
        ]);
    }

    /** Detail view of a single log entry */
    public function show($id)
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            return;
        }

        $log = $this->db->fetch(
            "SELECT al.*, u.username AS changed_by_name, e.full_name AS changed_by_full_name
             FROM audit_logs al
             LEFT JOIN users u ON u.user_id = al.changed_by
             LEFT JOIN employees e ON e.employee_id = u.employee_id
             WHERE al.log_id = ?",
            [$id]
        );

        if (!$log) {
            flash('error', __('audit_log_not_found'));
            $this->redirect('/admin/audit-logs');
            return;
        }

        $this->render('audit/log_detail', [
            'pageTitle' => __('menu_audit_log') . ' #' . $id,
            'log'       => $log,
        ]);
    }
}
