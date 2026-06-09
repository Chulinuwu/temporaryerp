<?php
/**
 * PEGASUS ERP — Exchange Rate Master (#10)
 * Manage currency pairs with From/To validity periods.
 */

class ExchangeRateController extends Controller
{
    /** List all exchange rates */
    public function index()
    {
        $this->requireAuth();

        $filters = [
            'from'   => sanitize($_GET['from'] ?? ''),
            'to'     => sanitize($_GET['to'] ?? ''),
            'active' => sanitize($_GET['active'] ?? '1'),
            'date'   => sanitize($_GET['date'] ?? ''),
        ];

        $sql = "SELECT er.*, fc.currency_name AS from_name, tc.currency_name AS to_name
                FROM exchange_rates er
                LEFT JOIN currencies fc ON fc.currency_code = er.from_currency
                LEFT JOIN currencies tc ON tc.currency_code = er.to_currency
                WHERE er.is_deleted = FALSE";
        $params = [];

        if ($filters['from'] !== '') { $sql .= " AND er.from_currency = ?"; $params[] = $filters['from']; }
        if ($filters['to']   !== '') { $sql .= " AND er.to_currency = ?";   $params[] = $filters['to']; }
        if ($filters['active'] === '1') {
            $sql .= " AND er.effective_from <= CURRENT_DATE
                      AND (er.effective_to IS NULL OR er.effective_to >= CURRENT_DATE)";
        }
        if ($filters['date'] !== '') {
            $sql .= " AND er.effective_from <= ? AND (er.effective_to IS NULL OR er.effective_to >= ?)";
            $params[] = $filters['date'];
            $params[] = $filters['date'];
        }

        $sql .= " ORDER BY er.from_currency, er.to_currency, er.effective_from DESC";
        $rates = $this->db->fetchAll($sql, $params);

        $currencies = $this->db->fetchAll(
            "SELECT * FROM currencies WHERE is_active = TRUE ORDER BY sort_order"
        );

        $this->render('master/exchange_rates', [
            'pageTitle'  => __('menu_exchange_rates'),
            'rates'      => $rates ?: [],
            'currencies' => $currencies ?: [],
            'filters'    => $filters,
        ]);
    }

    /** Save (create / update) */
    public function save()
    {
        $this->requireAuth();
        $user = $this->getCurrentUser();

        $rateId        = $_POST['rate_id'] ?? null;
        $from          = strtoupper(sanitize($_POST['from_currency'] ?? ''));
        $to            = strtoupper(sanitize($_POST['to_currency'] ?? ''));
        $rate          = floatval($_POST['rate'] ?? 0);
        $effFrom       = $_POST['effective_from'] ?: date('Y-m-d');
        $effTo         = $_POST['effective_to'] ?: null;
        $notes         = sanitize($_POST['notes'] ?? '');
        $autoReverse   = !empty($_POST['auto_reverse']);

        if (!$from || !$to || $rate <= 0) {
            flash('error', __('exchange_rate_invalid'));
            $this->redirect('/master/exchange-rates');
            return;
        }

        if ($rateId) {
            $this->db->execute(
                "UPDATE exchange_rates SET from_currency=?, to_currency=?, rate=?, effective_from=?,
                        effective_to=?, notes=?, updated_by=?, updated_at=NOW()
                 WHERE rate_id=?",
                [$from, $to, $rate, $effFrom, $effTo, $notes, $user['user_id'] ?? null, $rateId]
            );
        } else {
            $this->db->execute(
                "INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_from, effective_to, notes, created_by)
                 VALUES (?,?,?,?,?,?,?)",
                [$from, $to, $rate, $effFrom, $effTo, $notes, $user['user_id'] ?? null]
            );
            // Auto-create reverse rate
            if ($autoReverse && $from !== $to) {
                $reverse = round(1.0 / $rate, 8);
                $this->db->execute(
                    "INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_from, effective_to, notes, created_by)
                     VALUES (?,?,?,?,?,?,?)",
                    [$to, $from, $reverse, $effFrom, $effTo, ($notes ? $notes . ' (auto-reverse)' : 'auto-reverse'), $user['user_id'] ?? null]
                );
            }
        }

        flash('success', __('exchange_rate_saved'));
        $this->redirect('/master/exchange-rates');
    }

    public function delete($id)
    {
        $this->requireAuth();
        $this->db->execute("UPDATE exchange_rates SET is_deleted=TRUE WHERE rate_id=?", [$id]);
        flash('success', __('exchange_rate_deleted'));
        $this->redirect('/master/exchange-rates');
    }

    /** API: get latest rate for a currency pair on a given date */
    public function apiLatestRate()
    {
        $this->requireAuth();
        $from = strtoupper(sanitize($_GET['from'] ?? ''));
        $to   = strtoupper(sanitize($_GET['to'] ?? 'THB'));
        $date = $_GET['date'] ?: date('Y-m-d');

        if ($from === $to) { $this->json(['rate' => 1.0]); return; }

        $row = $this->db->fetch(
            "SELECT rate FROM exchange_rates
             WHERE from_currency = ? AND to_currency = ?
               AND is_deleted = FALSE
               AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to >= ?)
             ORDER BY effective_from DESC LIMIT 1",
            [$from, $to, $date, $date]
        );

        $this->json(['rate' => $row ? floatval($row['rate']) : null]);
    }
}
