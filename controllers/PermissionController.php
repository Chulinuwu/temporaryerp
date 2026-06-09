<?php
/**
 * PEGASUS ERP — Permission Master (#18)
 * Admin-only role/permission management
 */

class PermissionController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            return;
        }

        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY role_code");
        $permissions = $this->db->fetchAll(
            "SELECT * FROM permissions ORDER BY module, permission_code"
        );
        $assignments = $this->db->fetchAll(
            "SELECT role_code, permission_code FROM role_permissions"
        );

        // Build matrix lookup [role][perm] => true
        $matrix = [];
        foreach ($assignments as $a) {
            $matrix[$a['role_code']][$a['permission_code']] = true;
        }

        // Group permissions by module
        $byModule = [];
        foreach ($permissions as $p) {
            $byModule[$p['module']][] = $p;
        }

        $this->render('admin/permissions', [
            'pageTitle'   => __('menu_permissions'),
            'roles'       => $roles ?: [],
            'permissions' => $permissions ?: [],
            'byModule'    => $byModule,
            'matrix'      => $matrix,
        ]);
    }

    public function save()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            return;
        }

        try {
            $this->db->beginTransaction();
            // Wipe and rebuild
            $this->db->execute("DELETE FROM role_permissions");

            $perms = $_POST['perm'] ?? [];  // ['ADMIN' => ['sales.view'=>'1', ...], ...]
            foreach ($perms as $role => $list) {
                foreach ($list as $code => $on) {
                    if ($on) {
                        $this->db->execute(
                            "INSERT INTO role_permissions (role_code, permission_code) VALUES (?, ?)",
                            [$role, $code]
                        );
                    }
                }
            }

            $this->db->commit();
            flash('success', __('permissions_saved'));
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('PermissionController::save - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/admin/permissions');
    }
}
