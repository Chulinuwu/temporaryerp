<?php
/**
 * PEGASUS ERP — Approval Center
 *
 * Separate admin-approval queues for 4 entity types:
 *   - Customers        (/approvals/customers)
 *   - Suppliers        (/approvals/suppliers)
 *   - Quotations       (/approvals/quotations)
 *   - Purchase Orders  (/approvals/purchase-orders)
 *
 * Staff saves → PENDING; ADMIN/MANAGER approves → APPROVED (active in masters/docs).
 */
class ApprovalController extends Controller
{
    private function requireApprover(): void
    {
        $this->requireAuth();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('approver_only_action'));
            $this->redirect('/dashboard');
            exit;
        }
    }

    /* =========================================================
     *  LIST PAGES
     * ========================================================= */

    public function customers()
    {
        $this->requireApprover();
        $status = sanitize($_GET['status'] ?? 'AWAITING');
        $where  = "c.is_deleted = FALSE";
        $params = [];
        if ($status === 'AWAITING') {
            $where .= " AND c.approval_status IN ('PENDING','PENDING_MANAGER','PENDING_CEO')";
        } else {
            $where .= " AND COALESCE(c.approval_status,'APPROVED') = ?";
            $params[] = $status;
        }
        $rows = $this->db->fetchAll(
            "SELECT c.customer_id AS id, c.customer_code AS code, c.customer_name AS name,
                    c.tax_id, c.country,
                    c.approval_status, c.created_at, c.approved_at,
                    c.submitted_at, c.manager_approved_at, c.ceo_approved_at,
                    c.rejected_at, c.rejection_reason,
                    u1.email AS created_by_email,
                    u2.email AS approved_by_email,
                    um.email AS manager_approved_by_email,
                    uc.email AS ceo_approved_by_email
             FROM customers c
             LEFT JOIN users u1 ON u1.user_id = c.created_by
             LEFT JOIN users u2 ON u2.user_id = c.approved_by
             LEFT JOIN users um ON um.user_id = c.manager_approved_by
             LEFT JOIN users uc ON uc.user_id = c.ceo_approved_by
             WHERE $where
             ORDER BY c.created_at DESC LIMIT 200",
            $params
        );
        $this->render('approvals/list', [
            'pageTitle' => __('approval_queue_customers'),
            'entity'    => 'customer',
            'entityTitle' => __('menu_customers'),
            'rows'      => $rows ?: [],
            'status'    => $status,
            'statusOptions' => ['AWAITING','PENDING','PENDING_MANAGER','PENDING_CEO','APPROVED','REJECTED'],
            'detailUrl' => fn($id) => '/master/customers/' . $id . '/edit',
        ]);
    }

    public function suppliers()
    {
        $this->requireApprover();
        $status = sanitize($_GET['status'] ?? 'AWAITING');
        $where  = "s.is_deleted = FALSE";
        $params = [];
        if ($status === 'AWAITING') {
            $where .= " AND s.approval_status IN ('PENDING','PENDING_MANAGER','PENDING_CEO')";
        } else {
            $where .= " AND COALESCE(s.approval_status,'APPROVED') = ?";
            $params[] = $status;
        }
        $rows = $this->db->fetchAll(
            "SELECT s.supplier_id AS id, s.supplier_code AS code, s.supplier_name AS name,
                    s.tax_id, s.country,
                    s.approval_status, s.created_at, s.approved_at,
                    s.submitted_at, s.manager_approved_at, s.ceo_approved_at,
                    s.rejected_at, s.rejection_reason,
                    u1.email AS created_by_email,
                    u2.email AS approved_by_email,
                    um.email AS manager_approved_by_email,
                    uc.email AS ceo_approved_by_email,
                    (SELECT COUNT(*) FROM supplier_attachments a
                       WHERE a.supplier_id = s.supplier_id AND a.is_deleted = FALSE) AS attachment_count
             FROM suppliers s
             LEFT JOIN users u1 ON u1.user_id = s.created_by
             LEFT JOIN users u2 ON u2.user_id = s.approved_by
             LEFT JOIN users um ON um.user_id = s.manager_approved_by
             LEFT JOIN users uc ON uc.user_id = s.ceo_approved_by
             WHERE $where
             ORDER BY s.created_at DESC LIMIT 200",
            $params
        );
        $this->render('approvals/list', [
            'pageTitle' => __('approval_queue_suppliers'),
            'entity'    => 'supplier',
            'entityTitle' => __('menu_suppliers'),
            'rows'      => $rows ?: [],
            'status'    => $status,
            'statusOptions' => ['AWAITING','PENDING','PENDING_MANAGER','PENDING_CEO','APPROVED','REJECTED'],
            'detailUrl' => fn($id) => '/master/suppliers/' . $id . '/edit',
        ]);
    }

    public function quotations()
    {
        $this->requireApprover();
        $status = sanitize($_GET['status'] ?? 'PENDING_APPROVAL');
        $rows = $this->db->fetchAll(
            "SELECT qh.quotation_id AS id, qh.quotation_no AS code,
                    COALESCE(qh.project_name, qh.quotation_no) AS name,
                    c.customer_name AS customer, qh.grand_total_thb AS amount,
                    qh.status AS approval_status, qh.created_at, qh.approved_at,
                    u1.email AS created_by_email, u2.email AS approved_by_email
             FROM quotation_headers qh
             LEFT JOIN customers c ON c.customer_id = qh.customer_id
             LEFT JOIN users u1 ON u1.user_id = qh.created_by
             LEFT JOIN users u2 ON u2.user_id = qh.approved_by
             WHERE qh.is_deleted = FALSE AND qh.status = ?
             ORDER BY qh.created_at DESC LIMIT 200",
            [$status]
        );
        $this->render('approvals/list', [
            'pageTitle' => __('approval_queue_quotations'),
            'entity'    => 'quotation',
            'entityTitle' => __('menu_quotations'),
            'rows'      => $rows ?: [],
            'status'    => $status,
            'statusOptions' => ['PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'DRAFT'],
            'detailUrl' => fn($id) => '/sales/quotations/' . $id,
        ]);
    }

    /**
     * Purchase Request approval queue — surfaces PRs awaiting any step.
     * Default = AWAITING (= QUOTES_PENDING / PENDING_MANAGER / PENDING_CEO).
     * Approval actions themselves live on the PR detail page.
     */
    public function purchaseRequests()
    {
        $this->requireApprover();
        $status = sanitize($_GET['status'] ?? 'AWAITING');
        $where  = "pr.is_deleted = FALSE";
        $params = [];
        if ($status === 'AWAITING') {
            $where .= " AND pr.status IN ('SUBMITTED','QUOTES_PENDING','PENDING_MANAGER','PENDING_CEO')";
        } else {
            $where .= " AND pr.status = ?";
            $params[] = $status;
        }
        $rows = $this->db->fetchAll(
            "SELECT pr.pr_id, pr.pr_no, pr.status, pr.request_date, pr.needed_by_date,
                    pr.est_total_thb, pr.justification,
                    e.full_name_jp AS requester_name_jp,
                    e.full_name_th AS requester_name_th,
                    s.supplier_name AS suggested_supplier_name,
                    p.pj_no, p.pj_name,
                    (SELECT COUNT(*) FROM purchase_request_quotes q
                       WHERE q.pr_id = pr.pr_id AND q.is_deleted = FALSE) AS quote_count,
                    pu.email AS purchasing_approver_email,
                    mu.email AS manager_approver_email
             FROM purchase_requests pr
             LEFT JOIN employees e ON e.employee_id = pr.requester_id
             LEFT JOIN suppliers s ON s.supplier_id = pr.suggested_supplier_id
             LEFT JOIN projects  p ON p.project_id = pr.project_id
             LEFT JOIN users pu ON pu.user_id = pr.purchasing_approved_by
             LEFT JOIN users mu ON mu.user_id = pr.manager_approved_by
             WHERE $where
             ORDER BY pr.request_date DESC, pr.pr_id DESC
             LIMIT 200",
            $params
        );
        $this->render('approvals/pr_list', [
            'pageTitle'   => __('approval_queue_prs'),
            'rows'        => $rows ?: [],
            'status'      => $status,
            'statusOptions' => ['AWAITING','SUBMITTED','QUOTES_PENDING','PENDING_MANAGER','PENDING_CEO','APPROVED','REJECTED','CONVERTED','CANCELLED'],
        ]);
    }

    public function purchaseOrders()
    {
        $this->requireApprover();
        // Default queue: anything awaiting manager OR CEO approval (new 2-step flow)
        $status = sanitize($_GET['status'] ?? 'AWAITING');
        $where = "po.is_deleted = FALSE";
        $params = [];
        if ($status === 'AWAITING') {
            $where .= " AND po.status IN ('PENDING_MANAGER','PENDING_CEO','PENDING_APPROVAL','PENDING')";
        } else {
            $where .= " AND po.status = ?";
            $params[] = $status;
        }
        $rows = $this->db->fetchAll(
            "SELECT po.po_id AS id, po.po_no AS code, po.po_no AS name,
                    s.supplier_name AS supplier, po.payment_amount AS amount,
                    po.status AS approval_status, po.created_at, po.approved_at,
                    u1.email AS created_by_email, u2.email AS approved_by_email
             FROM purchase_order_headers po
             LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
             LEFT JOIN users u1 ON u1.user_id = po.created_by
             LEFT JOIN users u2 ON u2.user_id = po.approved_by
             WHERE $where
             ORDER BY po.created_at DESC LIMIT 200",
            $params
        );
        $this->render('approvals/list', [
            'pageTitle' => __('approval_queue_pos'),
            'entity'    => 'po',
            'entityTitle' => __('menu_purchase_orders'),
            'rows'      => $rows ?: [],
            'status'    => $status,
            'statusOptions' => ['AWAITING','PENDING_MANAGER','PENDING_CEO','APPROVED','REJECTED','DRAFT'],
            'detailUrl' => fn($id) => '/purchasing/orders/' . $id,
        ]);
    }

    /* =========================================================
     *  ACTIONS (approve / reject)
     * ========================================================= */

    public function approveCustomer($id) { $this->doApprove('customers',      'customer_id', $id, true);  }
    public function rejectCustomer($id)  { $this->doApprove('customers',      'customer_id', $id, false); }
    public function approveSupplier($id) { $this->doApprove('suppliers',      'supplier_id', $id, true);  }
    public function rejectSupplier($id)  { $this->doApprove('suppliers',      'supplier_id', $id, false); }
    public function approveQuotation($id){ $this->doApproveStatus('quotation_headers','quotation_id',$id,true); }
    public function rejectQuotation($id) { $this->doApproveStatus('quotation_headers','quotation_id',$id,false); }
    /** PO approve from the queue: respects the new 2-step flow.
     *  PENDING_MANAGER → PENDING_CEO (if approver is manager+)
     *  PENDING_CEO     → APPROVED    (if approver is director+/CEO)
     *  legacy PENDING/PENDING_APPROVAL → APPROVED (back-compat)
     */
    public function approvePo($id)
    {
        $this->requireApprover();
        $user = $this->getCurrentUser();
        $po = $this->db->fetch(
            "SELECT status FROM purchase_order_headers WHERE po_id = ? AND is_deleted = FALSE",
            [$id]
        );
        if (!$po) { flash('error','PO not found'); $this->redirect('/approvals/purchase-orders'); return; }

        $st = $po['status'];
        if ($st === 'PENDING_MANAGER' && Auth::isManagerOrAbove()) {
            $this->db->query(
                "UPDATE purchase_order_headers
                 SET status='PENDING_CEO', manager_approved_by=?, manager_approved_at=NOW(),
                     updated_at=NOW()
                 WHERE po_id=? AND status='PENDING_MANAGER'",
                [$user['user_id'], $id]
            );
            flash('success', __('po_manager_approved'));
        } elseif ($st === 'PENDING_CEO' && Auth::isDirectorOrAbove()) {
            $this->db->query(
                "UPDATE purchase_order_headers
                 SET status='APPROVED', ceo_approved_by=?, ceo_approved_at=NOW(),
                     approved_by=?, approved_at=NOW(), approval_date=CURRENT_DATE,
                     updated_at=NOW()
                 WHERE po_id=? AND status='PENDING_CEO'",
                [$user['user_id'], $user['user_id'], $id]
            );
            flash('success', __('po_ceo_approved'));
        } elseif (in_array($st, ['PENDING','PENDING_APPROVAL'], true) && Auth::isManagerOrAbove()) {
            // back-compat: legacy single-step POs
            $this->doApproveStatus('purchase_order_headers','po_id',$id,true);
            return;
        } else {
            flash('error', __('msg_no_approval_permission'));
        }
        $this->redirect('/approvals/purchase-orders');
    }

    public function rejectPo($id)        { $this->doApproveStatus('purchase_order_headers','po_id',$id,false); }

    /**
     * For master tables (customers/suppliers) — implements the 2-step approval flow:
     *   PENDING (legacy) / PENDING_MANAGER → PENDING_CEO  (Admin Manager approves)
     *   PENDING_CEO                          → APPROVED   (CEO approves)
     * Rejection at either step → REJECTED.
     */
    private function doApprove(string $table, string $pk, int $id, bool $approve): void
    {
        $this->requireApprover();
        $user = $this->getCurrentUser();
        $row = $this->db->fetch("SELECT approval_status FROM $table WHERE $pk = ?", [$id]);
        if (!$row) {
            flash('error', __('not_found') ?? 'Not found');
            $this->redirect('/approvals/' . ($table === 'customers' ? 'customers' : 'suppliers'));
            return;
        }
        $st = $row['approval_status'];
        $redir = '/approvals/' . ($table === 'customers' ? 'customers' : 'suppliers');

        if (!$approve) {
            $reason = trim((string)($_POST['reason'] ?? ''));
            if ($reason === '') $reason = '(no reason given)';
            $this->db->query(
                "UPDATE $table
                 SET approval_status = 'REJECTED', rejected_by = ?, rejected_at = NOW(),
                     rejection_reason = ?, updated_at = NOW()
                 WHERE $pk = ?",
                [$user['user_id'] ?? null, $reason, $id]
            );
            flash('success', __('rejected_ok'));
            $this->redirect($redir); return;
        }

        // Approve path
        if (in_array($st, ['PENDING','PENDING_MANAGER','DRAFT'], true) && Auth::isManagerOrAbove()) {
            $this->db->query(
                "UPDATE $table
                 SET approval_status = 'PENDING_CEO',
                     manager_approved_by = ?, manager_approved_at = NOW(),
                     updated_at = NOW()
                 WHERE $pk = ?",
                [$user['user_id'] ?? null, $id]
            );
            flash('success', __('master_manager_approved') ?? __('approved_ok'));
        } elseif ($st === 'PENDING_CEO' && Auth::isDirectorOrAbove()) {
            $this->db->query(
                "UPDATE $table
                 SET approval_status = 'APPROVED',
                     ceo_approved_by = ?, ceo_approved_at = NOW(),
                     approved_by = ?, approved_at = NOW(),
                     updated_at = NOW()
                 WHERE $pk = ?",
                [$user['user_id'] ?? null, $user['user_id'] ?? null, $id]
            );
            flash('success', __('master_ceo_approved') ?? __('approved_ok'));
        } else {
            flash('error', __('msg_no_approval_permission'));
        }
        $this->redirect($redir);
    }

    /** For documents (quotations/POs) — update status column */
    private function doApproveStatus(string $table, string $pk, int $id, bool $approve): void
    {
        $this->requireApprover();
        $user = $this->getCurrentUser();
        $newStatus = $approve ? 'APPROVED' : 'REJECTED';

        $this->db->query(
            "UPDATE $table
                SET status = ?,
                    approved_by = ?,
                    approved_at = CASE WHEN ? THEN NOW() ELSE NULL END,
                    updated_at = NOW()
              WHERE $pk = ?",
            [$newStatus, $user['user_id'] ?? null, $approve ? 't' : 'f', $id]
        );

        flash('success', $approve ? __('approved_ok') : __('rejected_ok'));
        $kind = ($table === 'quotation_headers') ? 'quotations' : 'purchase-orders';
        $this->redirect('/approvals/' . $kind);
    }
}
