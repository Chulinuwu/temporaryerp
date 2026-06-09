<?php
/**
 * PEGASUS ERP - Kintai (Attendance Portal) Controller
 *
 * JobCan-inspired attendance portal that plugs into the existing Pegasus HR
 * tables (employees, attendance, leave_requests). Designed for employees to
 * self-service: view attendance, submit overtime / leave requests, etc.
 *
 * Routes mounted under /kintai/*
 */
class KintaiController extends Controller
{
    /**
     * Render a Kintai view inside the Kintai layout (not the ERP layout).
     */
    private function renderKintai($view, array $data = [])
    {
        // Minimal preview session — allows the portal to be browsed without
        // a full ERP login (e.g. during design review). Real employees see
        // their own session data populated by the ERP login flow.
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id']    = 0;
            $_SESSION['user']       = ['full_name' => 'Admin Account', 'role' => 'Administrator'];
            $_SESSION['staff_code'] = '9999';
        }

        extract($data);
        ob_start();
        require BASE_PATH . '/views/' . $view . '.php';
        $content = ob_get_clean();

        require BASE_PATH . '/views/layout/kintai.php';
    }

    /* -----------------------------------------------------------
       Dashboard / redirect root
       ----------------------------------------------------------- */
    public function index()
    {
        $this->redirect('/kintai/attendance');
    }

    /* -----------------------------------------------------------
       出勤簿 (Attendance log)
       ----------------------------------------------------------- */
    public function attendance()
    {
        $year  = (int) ($_GET['year']  ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));

        // Build day-by-day rows for the selected month.
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $rows = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $ts   = mktime(0, 0, 0, $month, $d, $year);
            $dow  = (int) date('w', $ts);
            $rows[] = [
                'date'      => sprintf('%04d/%02d/%02d', $year, $month, $d),
                'dow'       => ['日','月','火','水','木','金','土'][$dow],
                'dow_idx'   => $dow,
                'clock_in'  => '',
                'clock_out' => '',
                'work_h'    => '',
                'ot_h'      => '',
                'status'    => '',
            ];
        }

        $this->renderKintai('kintai/attendance', [
            'pageTitle'  => '出勤簿',
            'activeMenu' => 'attendance',
            'year'       => $year,
            'month'      => $month,
            'rows'       => $rows,
        ]);
    }

    /* -----------------------------------------------------------
       打刻修正 / 打刻修正申請
       ----------------------------------------------------------- */
    public function clockEdit()
    {
        $this->renderKintai('kintai/clock_edit', [
            'pageTitle'  => '打刻修正申請',
            'activeMenu' => 'requests',
            'activeSub'  => 'clock_fix',
        ]);
    }

    /* -----------------------------------------------------------
       工数管理
       ----------------------------------------------------------- */
    public function kosu()
    {
        $this->renderKintai('kintai/kosu', [
            'pageTitle'  => '工数管理',
            'activeMenu' => 'kosu',
            'activeSub'  => 'kosu_list',
        ]);
    }

    /* -----------------------------------------------------------
       休暇申請 (Leave application)
       ----------------------------------------------------------- */
    public function newLeave()
    {
        $this->renderKintai('kintai/leave_new', [
            'pageTitle'  => '新規休暇申請',
            'activeMenu' => 'requests',
            'activeSub'  => 'leave',
            'leaveTypes' => [
                '' => '選択してください',
                'paid'      => '有給休暇',
                'half_am'   => '半日休暇（午前）',
                'half_pm'   => '半日休暇（午後）',
                'special'   => '特別休暇',
                'sick'      => '病気休暇',
                'maternity' => '産前産後休暇',
                'absence'   => '欠勤',
            ],
        ]);
    }

    /* -----------------------------------------------------------
       残業申請 (Overtime)
       ----------------------------------------------------------- */
    public function overtimeList()
    {
        $this->renderKintai('kintai/overtime_list', [
            'pageTitle'  => '残業申請一覧',
            'activeMenu' => 'requests',
            'activeSub'  => 'overtime',
            'year'       => (int) date('Y'),
            'month'      => (int) date('n'),
            'records'    => [], // populated from DB in production
        ]);
    }

    public function newOvertime()
    {
        $this->renderKintai('kintai/overtime_new', [
            'pageTitle'  => '新規残業申請',
            'activeMenu' => 'requests',
            'activeSub'  => 'overtime',
        ]);
    }

    public function newEarlyOvertime()
    {
        $this->renderKintai('kintai/overtime_early_new', [
            'pageTitle'  => '新規早出残業申請',
            'activeMenu' => 'requests',
            'activeSub'  => 'early',
        ]);
    }

    /* -----------------------------------------------------------
       スタッフ設定 (Staff profile / settings)
       ----------------------------------------------------------- */
    public function staffSettings()
    {
        $user = $_SESSION['user'] ?? [];
        $this->renderKintai('kintai/staff_settings', [
            'pageTitle'  => 'スタッフ設定',
            'activeMenu' => 'staff_settings',
            'profile'    => [
                'name'       => $user['full_name'] ?? 'Admin Account',
                'group'      => 'ADMIN Administration',
                'sub_group'  => '',
                'staff_type' => 'ไม่ระบุ',
                'phone'      => '',
                'email'      => 'info@tomastc.com',
            ],
        ]);
    }
}
