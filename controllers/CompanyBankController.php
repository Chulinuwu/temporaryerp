<?php
/**
 * PEGASUS ERP — Company Bank Accounts Master
 * Manages own bank accounts shown on customer invoices.
 */
class CompanyBankController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $accounts = $this->db->fetchAll(
            "SELECT * FROM company_bank_accounts ORDER BY is_default DESC, sort_order, cba_id"
        );
        $currencies = $this->db->fetchAll(
            "SELECT currency_code, currency_name FROM currencies WHERE is_active = TRUE ORDER BY sort_order"
        );
        $this->render('master/company_bank_accounts', [
            'pageTitle'  => __('menu_company_bank'),
            'accounts'   => $accounts ?: [],
            'currencies' => $currencies ?: [],
        ]);
    }

    public function save()
    {
        $this->requireAuth();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('manager_only_action'));
            $this->redirect('/master/company-bank');
            return;
        }

        $user = $this->getCurrentUser();
        $id = $_POST['cba_id'] ?? null;
        $data = [
            'bank_name'     => sanitize($_POST['bank_name'] ?? ''),
            'bank_name_th'  => sanitize($_POST['bank_name_th'] ?? ''),
            'branch'        => sanitize($_POST['branch'] ?? ''),
            'branch_th'     => sanitize($_POST['branch_th'] ?? ''),
            'account_type'  => sanitize($_POST['account_type'] ?? 'CURRENT'),
            'account_no'    => sanitize($_POST['account_no'] ?? ''),
            'account_name'  => sanitize($_POST['account_name'] ?? ''),
            'currency_code' => sanitize($_POST['currency_code'] ?? 'THB'),
            'swift_code'    => sanitize($_POST['swift_code'] ?? ''),
            'notes'         => sanitize($_POST['notes'] ?? ''),
            'is_default'    => !empty($_POST['is_default']),
            'is_active'     => !empty($_POST['is_active']),
            'sort_order'    => intval($_POST['sort_order'] ?? 0),
        ];

        if (!$data['bank_name'] || !$data['account_no'] || !$data['account_name']) {
            flash('error', __('msg_invalid_input'));
            $this->redirect('/master/company-bank');
            return;
        }

        // If setting default, clear other defaults for same currency
        if ($data['is_default']) {
            $this->db->query(
                "UPDATE company_bank_accounts SET is_default = FALSE WHERE currency_code = ?",
                [$data['currency_code']]
            );
        }

        if ($id) {
            $this->db->query(
                "UPDATE company_bank_accounts SET
                    bank_name=?, bank_name_th=?, branch=?, branch_th=?, account_type=?,
                    account_no=?, account_name=?, currency_code=?, swift_code=?, notes=?,
                    is_default=?, is_active=?, sort_order=?, updated_by=?, updated_at=NOW()
                 WHERE cba_id=?",
                [$data['bank_name'], $data['bank_name_th'], $data['branch'], $data['branch_th'],
                 $data['account_type'], $data['account_no'], $data['account_name'],
                 $data['currency_code'], $data['swift_code'], $data['notes'],
                 $data['is_default'] ? 't' : 'f', $data['is_active'] ? 't' : 'f',
                 $data['sort_order'], $user['user_id'] ?? null, $id]
            );
        } else {
            $this->db->query(
                "INSERT INTO company_bank_accounts
                  (bank_name, bank_name_th, branch, branch_th, account_type,
                   account_no, account_name, currency_code, swift_code, notes,
                   is_default, is_active, sort_order, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$data['bank_name'], $data['bank_name_th'], $data['branch'], $data['branch_th'],
                 $data['account_type'], $data['account_no'], $data['account_name'],
                 $data['currency_code'], $data['swift_code'], $data['notes'],
                 $data['is_default'] ? 't' : 'f', $data['is_active'] ? 't' : 'f',
                 $data['sort_order'], $user['user_id'] ?? null]
            );
        }

        flash('success', __('company_bank_saved'));
        $this->redirect('/master/company-bank');
    }

    public function delete($id)
    {
        $this->requireAuth();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('manager_only_action'));
            $this->redirect('/master/company-bank');
            return;
        }
        $this->db->query("DELETE FROM company_bank_accounts WHERE cba_id = ?", [$id]);
        flash('success', __('company_bank_deleted'));
        $this->redirect('/master/company-bank');
    }
}
