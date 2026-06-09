<?php
/**
 * PEGASUS ERP - Accounting Controller
 * Journal entries, general ledger, financial statements
 */

class AccountingController extends Controller
{
    // ── Journal Entries ───────────────────────────────────────────────

    public function newJournal()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $db = Database::getInstance();

        try {
            $accounts = $db->fetchAll(
                "SELECT account_id, account_code, account_name, account_type
                 FROM accounts WHERE is_deleted = FALSE ORDER BY account_code"
            );

            $this->render('accounting/journal_entry', [
                'pageTitle' => 'New Journal Entry',
                'journal' => null,
                'lines' => [],
                'accounts' => $accounts ?: []
            ]);
        } catch (Exception $e) {
            error_log('AccountingController::newJournal - ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/accounting/journal');
        }
    }

    public function storeJournal()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $jeDate = sanitize($_POST['je_date'] ?? '');
            $period = sanitize($_POST['period'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $referenceType = sanitize($_POST['reference_type'] ?? '');
            $referenceNo = sanitize($_POST['reference_no'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            // Line arrays
            $accountCodes = $_POST['account_code'] ?? [];
            $lineDescriptions = $_POST['line_description'] ?? [];
            $debitAmounts = $_POST['debit_amount'] ?? [];
            $creditAmounts = $_POST['credit_amount'] ?? [];

            if (empty($jeDate)) {
                flash('error', 'Journal date is required.');
                $this->redirect('/accounting/journal/new');
                return;
            }

            // Validate debit = credit
            $totalDebit = 0;
            $totalCredit = 0;
            $lineData = [];

            for ($i = 0; $i < count($accountCodes); $i++) {
                $debit = floatval($debitAmounts[$i] ?? 0);
                $credit = floatval($creditAmounts[$i] ?? 0);
                $totalDebit += $debit;
                $totalCredit += $credit;

                if (($debit > 0 || $credit > 0) && !empty($accountCodes[$i])) {
                    $lineData[] = [
                        'account_code' => sanitize($accountCodes[$i]),
                        'description' => sanitize($lineDescriptions[$i] ?? ''),
                        'debit_amount' => $debit,
                        'credit_amount' => $credit
                    ];
                }
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                flash('error', 'Total debit must equal total credit. Difference: ' . number_format($totalDebit - $totalCredit, 2));
                $this->redirect('/accounting/journal/new');
                return;
            }

            if (empty($lineData)) {
                flash('error', 'At least one journal line is required.');
                $this->redirect('/accounting/journal/new');
                return;
            }

            $db->beginTransaction();

            // Generate JE number
            $jeNo = $this->generateJeNo($db);

            // Determine period from date if not provided
            if (empty($period)) {
                $period = date('Y-m', strtotime($jeDate));
            }

            // Insert header
            $row = $db->fetch(
                "INSERT INTO journal_entries
                 (je_no, je_date, period, description, reference_type,
                  division_id, total_debit, total_credit, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,'DRAFT',?)
                 RETURNING je_id",
                [$jeNo, $jeDate, $period, $description, $referenceType,
                 $divisionId, $totalDebit, $totalCredit, $user['user_id']]
            );
            $jeId = $row['je_id'];

            // Insert lines
            $lineNo = 1;
            foreach ($lineData as $line) {
                $db->query(
                    "INSERT INTO journal_lines
                     (je_id, line_no, account_code, description, debit_amount, credit_amount)
                     VALUES (?,?,?,?,?,?)",
                    [$jeId, $lineNo++, $line['account_code'], $line['description'],
                     $line['debit_amount'], $line['credit_amount']]
                );
            }

            $db->commit();
            flash('success', "Journal Entry {$jeNo} created.");
            $this->redirect('/accounting/journal/' . $jeId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('AccountingController::storeJournal - ' . $e->getMessage());
            flash('error', 'Failed to create journal entry.');
            $this->redirect('/accounting/journal/new');
        }
    }

    public function journalList()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $db = Database::getInstance();

        try {
            $dateFrom = sanitize($_GET['date_from'] ?? '');
            $dateTo = sanitize($_GET['date_to'] ?? '');
            $period = sanitize($_GET['period'] ?? '');
            $status = sanitize($_GET['status'] ?? '');

            $sql = "SELECT * FROM journal_entries WHERE is_deleted = FALSE";
            $params = [];

            if (!empty($dateFrom)) {
                $sql .= " AND je_date >= ?";
                $params[] = $dateFrom;
            }

            if (!empty($dateTo)) {
                $sql .= " AND je_date <= ?";
                $params[] = $dateTo;
            }

            if (!empty($period)) {
                $sql .= " AND period = ?";
                $params[] = $period;
            }

            if (!empty($status)) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY je_date DESC, je_no DESC";

            $journals = $db->fetchAll($sql, $params);

            $this->render('accounting/journal_entry', [
                'pageTitle' => 'Journal Entries',
                'journals' => $journals ?: [],
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'period' => $period,
                'status' => $status
            ]);
        } catch (Exception $e) {
            error_log('AccountingController::journalList - ' . $e->getMessage());
            flash('error', 'Failed to load journal entries.');
            $this->render('accounting/journal_entry', [
                'pageTitle' => 'Journal Entries',
                'journals' => [],
                'dateFrom' => '',
                'dateTo' => '',
                'period' => '',
                'status' => ''
            ]);
        }
    }

    public function showJournal($id)
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $db = Database::getInstance();

        try {
            $journal = $db->fetch(
                "SELECT * FROM journal_entries WHERE je_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$journal) {
                flash('error', 'Journal entry not found.');
                $this->redirect('/accounting/journal');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT jl.*, a.account_name
                 FROM journal_lines jl
                 LEFT JOIN accounts a ON a.account_code = jl.account_code
                 WHERE jl.je_id = ? ORDER BY jl.line_no",
                [$id]
            );

            $this->render('accounting/journal_entry', [
                'pageTitle' => 'JE ' . $journal['je_no'],
                'journal' => $journal,
                'lines' => $lines ?: []
            ]);
        } catch (Exception $e) {
            error_log('AccountingController::showJournal - ' . $e->getMessage());
            flash('error', 'Failed to load journal entry.');
            $this->redirect('/accounting/journal');
        }
    }

    public function postJournal($id)
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $this->requireRole(['admin', 'accountant']);
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();

            $journal = $db->fetch(
                "SELECT status FROM journal_entries WHERE je_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$journal) {
                flash('error', 'Journal entry not found.');
                $this->redirect('/accounting/journal');
                return;
            }

            if ($journal['status'] === 'POSTED') {
                flash('error', 'Journal is already posted.');
                $this->redirect('/accounting/journal/' . $id);
                return;
            }

            $db->query(
                "UPDATE journal_entries SET status = 'POSTED', posted_by = ?, posted_at = NOW(),
                 updated_at = NOW() WHERE je_id = ?",
                [$user['user_id'], $id]
            );

            flash('success', 'Journal entry posted.');
            $this->redirect('/accounting/journal/' . $id);
        } catch (Exception $e) {
            error_log('AccountingController::postJournal - ' . $e->getMessage());
            flash('error', 'Failed to post journal entry.');
            $this->redirect('/accounting/journal/' . $id);
        }
    }

    // ── General Ledger ────────────────────────────────────────────────

    public function ledger()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $db = Database::getInstance();

        try {
            $accountCode = sanitize($_GET['account_code'] ?? '');
            $dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
            $dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));

            $transactions = [];
            $account = null;

            if (!empty($accountCode)) {
                $account = $db->fetch(
                    "SELECT * FROM accounts WHERE account_code = ? AND is_deleted = FALSE",
                    [$accountCode]
                );

                if ($account) {
                    // Opening balance: sum of all postings before dateFrom
                    $opening = $db->fetch(
                        "SELECT COALESCE(SUM(jl.debit_amount), 0) as total_debit,
                                COALESCE(SUM(jl.credit_amount), 0) as total_credit
                         FROM journal_lines jl
                         JOIN journal_entries je ON je.je_id = jl.je_id
                         WHERE jl.account_code = ? AND je.status = 'POSTED'
                         AND je.is_deleted = FALSE AND je.je_date < ?",
                        [$accountCode, $dateFrom]
                    );

                    // Transactions in date range
                    $transactions = $db->fetchAll(
                        "SELECT jl.*, je.je_no, je.je_date, je.description as je_description
                         FROM journal_lines jl
                         JOIN journal_entries je ON je.je_id = jl.je_id
                         WHERE jl.account_code = ? AND je.status = 'POSTED'
                         AND je.is_deleted = FALSE
                         AND je.je_date >= ? AND je.je_date <= ?
                         ORDER BY je.je_date, je.je_no, jl.line_no",
                        [$accountCode, $dateFrom, $dateTo]
                    );
                }
            }

            $accounts = $db->fetchAll(
                "SELECT account_code, account_name, account_type FROM accounts WHERE is_deleted = FALSE ORDER BY account_code"
            );

            $this->render('accounting/ledger', [
                'pageTitle' => 'General Ledger',
                'account' => $account,
                'transactions' => $transactions ?: [],
                'accounts' => $accounts ?: [],
                'accountCode' => $accountCode,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'opening' => $opening ?? ['total_debit' => 0, 'total_credit' => 0]
            ]);
        } catch (Exception $e) {
            error_log('AccountingController::ledger - ' . $e->getMessage());
            flash('error', 'Failed to load ledger.');
            $this->redirect('/accounting/journal');
        }
    }

    // ── Financial Statements ──────────────────────────────────────────

    public function profitLoss()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $db = Database::getInstance();

        try {
            $dateFrom = sanitize($_GET['date_from'] ?? date('Y-01-01'));
            $dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));

            // Revenue accounts
            $revenue = $db->fetchAll(
                "SELECT a.account_code, a.account_name,
                        COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) as balance
                 FROM accounts a
                 LEFT JOIN journal_lines jl ON jl.account_code = a.account_code
                 LEFT JOIN journal_entries je ON je.je_id = jl.je_id
                    AND je.status = 'POSTED' AND je.is_deleted = FALSE
                    AND je.je_date >= ? AND je.je_date <= ?
                 WHERE a.account_type = 'REVENUE' AND a.is_deleted = FALSE
                 GROUP BY a.account_code, a.account_name
                 HAVING COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) != 0
                 ORDER BY a.account_code",
                [$dateFrom, $dateTo]
            );

            // COGS accounts
            $cogs = $db->fetchAll(
                "SELECT a.account_code, a.account_name,
                        COALESCE(SUM(jl.debit_amount), 0) - COALESCE(SUM(jl.credit_amount), 0) as balance
                 FROM accounts a
                 LEFT JOIN journal_lines jl ON jl.account_code = a.account_code
                 LEFT JOIN journal_entries je ON je.je_id = jl.je_id
                    AND je.status = 'POSTED' AND je.is_deleted = FALSE
                    AND je.je_date >= ? AND je.je_date <= ?
                 WHERE a.account_type = 'COGS' AND a.is_deleted = FALSE
                 GROUP BY a.account_code, a.account_name
                 HAVING COALESCE(SUM(jl.debit_amount), 0) - COALESCE(SUM(jl.credit_amount), 0) != 0
                 ORDER BY a.account_code",
                [$dateFrom, $dateTo]
            );

            // Expense accounts
            $expenses = $db->fetchAll(
                "SELECT a.account_code, a.account_name,
                        COALESCE(SUM(jl.debit_amount), 0) - COALESCE(SUM(jl.credit_amount), 0) as balance
                 FROM accounts a
                 LEFT JOIN journal_lines jl ON jl.account_code = a.account_code
                 LEFT JOIN journal_entries je ON je.je_id = jl.je_id
                    AND je.status = 'POSTED' AND je.is_deleted = FALSE
                    AND je.je_date >= ? AND je.je_date <= ?
                 WHERE a.account_type = 'EXPENSE' AND a.is_deleted = FALSE
                 GROUP BY a.account_code, a.account_name
                 HAVING COALESCE(SUM(jl.debit_amount), 0) - COALESCE(SUM(jl.credit_amount), 0) != 0
                 ORDER BY a.account_code",
                [$dateFrom, $dateTo]
            );

            $totalRevenue = array_sum(array_column($revenue ?: [], 'balance'));
            $totalCogs = array_sum(array_column($cogs ?: [], 'balance'));
            $totalExpense = array_sum(array_column($expenses ?: [], 'balance'));
            $grossProfit = $totalRevenue - $totalCogs;
            $netProfit = $grossProfit - $totalExpense;

            $this->render('accounting/pl', [
                'pageTitle' => 'Profit & Loss Statement',
                'revenue' => $revenue ?: [],
                'cogs' => $cogs ?: [],
                'expenses' => $expenses ?: [],
                'totalRevenue' => $totalRevenue,
                'totalCogs' => $totalCogs,
                'totalExpense' => $totalExpense,
                'grossProfit' => $grossProfit,
                'netProfit' => $netProfit,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);
        } catch (Exception $e) {
            error_log('AccountingController::profitLoss - ' . $e->getMessage());
            flash('error', 'Failed to generate P&L.');
            $this->redirect('/accounting/journal');
        }
    }

    public function balanceSheet()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');
        $db = Database::getInstance();

        try {
            $asOfDate = sanitize($_GET['as_of_date'] ?? date('Y-m-d'));

            // Asset accounts
            $assets = $db->fetchAll(
                "SELECT a.account_code, a.account_name,
                        COALESCE(SUM(jl.debit_amount), 0) - COALESCE(SUM(jl.credit_amount), 0) as balance
                 FROM accounts a
                 LEFT JOIN journal_lines jl ON jl.account_code = a.account_code
                 LEFT JOIN journal_entries je ON je.je_id = jl.je_id
                    AND je.status = 'POSTED' AND je.is_deleted = FALSE
                    AND je.je_date <= ?
                 WHERE a.account_type = 'ASSET' AND a.is_deleted = FALSE
                 GROUP BY a.account_code, a.account_name
                 HAVING COALESCE(SUM(jl.debit_amount), 0) - COALESCE(SUM(jl.credit_amount), 0) != 0
                 ORDER BY a.account_code",
                [$asOfDate]
            );

            // Liability accounts
            $liabilities = $db->fetchAll(
                "SELECT a.account_code, a.account_name,
                        COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) as balance
                 FROM accounts a
                 LEFT JOIN journal_lines jl ON jl.account_code = a.account_code
                 LEFT JOIN journal_entries je ON je.je_id = jl.je_id
                    AND je.status = 'POSTED' AND je.is_deleted = FALSE
                    AND je.je_date <= ?
                 WHERE a.account_type = 'LIABILITY' AND a.is_deleted = FALSE
                 GROUP BY a.account_code, a.account_name
                 HAVING COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) != 0
                 ORDER BY a.account_code",
                [$asOfDate]
            );

            // Equity accounts
            $equity = $db->fetchAll(
                "SELECT a.account_code, a.account_name,
                        COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) as balance
                 FROM accounts a
                 LEFT JOIN journal_lines jl ON jl.account_code = a.account_code
                 LEFT JOIN journal_entries je ON je.je_id = jl.je_id
                    AND je.status = 'POSTED' AND je.is_deleted = FALSE
                    AND je.je_date <= ?
                 WHERE a.account_type = 'EQUITY' AND a.is_deleted = FALSE
                 GROUP BY a.account_code, a.account_name
                 HAVING COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) != 0
                 ORDER BY a.account_code",
                [$asOfDate]
            );

            // Retained earnings (net of revenue - cogs - expenses)
            $retainedEarnings = $db->fetch(
                "SELECT
                    COALESCE(SUM(CASE WHEN a.account_type = 'REVENUE'
                        THEN jl.credit_amount - jl.debit_amount ELSE 0 END), 0)
                  - COALESCE(SUM(CASE WHEN a.account_type IN ('COGS','EXPENSE')
                        THEN jl.debit_amount - jl.credit_amount ELSE 0 END), 0) as balance
                 FROM journal_lines jl
                 JOIN journal_entries je ON je.je_id = jl.je_id
                 JOIN accounts a ON a.account_code = jl.account_code
                 WHERE je.status = 'POSTED' AND je.is_deleted = FALSE AND je.je_date <= ?
                 AND a.account_type IN ('REVENUE','COGS','EXPENSE')",
                [$asOfDate]
            );

            $totalAssets = array_sum(array_column($assets ?: [], 'balance'));
            $totalLiabilities = array_sum(array_column($liabilities ?: [], 'balance'));
            $totalEquity = array_sum(array_column($equity ?: [], 'balance'));
            $retainedEarningsBalance = floatval($retainedEarnings['balance'] ?? 0);

            $this->render('accounting/bs', [
                'pageTitle' => 'Balance Sheet',
                'assets' => $assets ?: [],
                'liabilities' => $liabilities ?: [],
                'equity' => $equity ?: [],
                'totalAssets' => $totalAssets,
                'totalLiabilities' => $totalLiabilities,
                'totalEquity' => $totalEquity,
                'retainedEarnings' => $retainedEarningsBalance,
                'asOfDate' => $asOfDate
            ]);
        } catch (Exception $e) {
            error_log('AccountingController::balanceSheet - ' . $e->getMessage());
            flash('error', 'Failed to generate balance sheet.');
            $this->redirect('/accounting/journal');
        }
    }

    // ── Cash Flow ─────────────────────────────────────────────────────

    public function cashFlowActual()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $months = intval($_GET['months'] ?? 6);
            $data = [];

            for ($i = $months - 1; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-{$i} months"));
                $label = date('M Y', strtotime("-{$i} months"));

                $income = $db->fetch(
                    "SELECT COALESCE(SUM(amount_thb), 0) as total
                     FROM ar_payments WHERE is_deleted = FALSE
                     AND TO_CHAR(payment_date, 'YYYY-MM') = ?",
                    [$month]
                );

                $expense = $db->fetch(
                    "SELECT COALESCE(SUM(amount_thb), 0) as total
                     FROM ap_payments WHERE is_deleted = FALSE
                     AND TO_CHAR(payment_date, 'YYYY-MM') = ?",
                    [$month]
                );

                $data[] = [
                    'month' => $label,
                    'income' => floatval($income['total'] ?? 0),
                    'expense' => floatval($expense['total'] ?? 0),
                    'net' => floatval($income['total'] ?? 0) - floatval($expense['total'] ?? 0)
                ];
            }

            $this->json(['data' => $data]);
        } catch (Exception $e) {
            error_log('AccountingController::cashFlowActual - ' . $e->getMessage());
            $this->json(['error' => 'Failed to load cash flow data.'], 500);
        }
    }

    public function cashFlowForecast()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $months = intval($_GET['months'] ?? 3);
            $data = [];

            for ($i = 1; $i <= $months; $i++) {
                $monthStart = date('Y-m-01', strtotime("+{$i} months"));
                $monthEnd = date('Y-m-t', strtotime("+{$i} months"));
                $label = date('M Y', strtotime("+{$i} months"));

                // Expected AR collections (open/partial invoices due in this month)
                $expectedIncome = $db->fetch(
                    "SELECT COALESCE(SUM(balance_thb), 0) as total
                     FROM ar_invoices
                     WHERE is_deleted = FALSE AND status IN ('OPEN','PARTIAL')
                     AND due_date >= ? AND due_date <= ?",
                    [$monthStart, $monthEnd]
                );

                // Expected AP payments (open/partial invoices due in this month)
                $expectedExpense = $db->fetch(
                    "SELECT COALESCE(SUM(balance_thb), 0) as total
                     FROM ap_invoices
                     WHERE is_deleted = FALSE AND status IN ('OPEN','PARTIAL')
                     AND due_date >= ? AND due_date <= ?",
                    [$monthStart, $monthEnd]
                );

                $data[] = [
                    'month' => $label,
                    'expected_income' => floatval($expectedIncome['total'] ?? 0),
                    'expected_expense' => floatval($expectedExpense['total'] ?? 0),
                    'net' => floatval($expectedIncome['total'] ?? 0) - floatval($expectedExpense['total'] ?? 0)
                ];
            }

            $this->json(['data' => $data]);
        } catch (Exception $e) {
            error_log('AccountingController::cashFlowForecast - ' . $e->getMessage());
            $this->json(['error' => 'Failed to load forecast data.'], 500);
        }
    }

    /**
     * Generate JE number
     */
    private function generateJeNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'JE-' . $yearMonth;

        $row = $db->fetch(
            "SELECT je_no FROM journal_entries
             WHERE je_no LIKE ?
             ORDER BY je_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['je_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
