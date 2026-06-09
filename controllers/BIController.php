<?php
/**
 * PEGASUS ERP - BI Dashboard Builder Controller
 */

class BIController extends Controller
{
    private $schema;

    public function __construct()
    {
        parent::__construct();
        $this->schema = require BASE_PATH . '/config/bi_schema.php';
    }

    // ── Dashboard List ──
    public function index()
    {
        $this->requireAuth();
        $user = $this->getCurrentUser();
        $userId = $user['user_id'];
        $role   = $user['role'] ?? '';

        $sql = "SELECT * FROM bi_dashboards WHERE is_deleted = FALSE
                AND (owner_user_id = ? OR is_shared = TRUE)
                ORDER BY updated_at DESC";
        $dashboards = $this->db->fetchAll($sql, [$userId]);

        $this->render('bi/index', [
            'pageTitle'  => __('bi_dashboards'),
            'dashboards' => $dashboards,
            'userId'     => $userId,
        ]);
    }

    // ── Create Form ──
    public function create()
    {
        $this->requireAuth();
        $this->render('bi/builder', [
            'pageTitle'  => __('bi_new_dashboard'),
            'dashboard'  => null,
            'widgets'    => [],
            'isNew'      => true,
        ]);
    }

    // ── Store New Dashboard ──
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->getCurrentUser();

        $name   = sanitize($this->input('dashboard_name', 'Untitled'));
        $nameJp = sanitize($this->input('dashboard_name_jp', ''));
        $nameTh = sanitize($this->input('dashboard_name_th', ''));
        $desc   = sanitize($this->input('description', ''));

        $sql = "INSERT INTO bi_dashboards (dashboard_name, dashboard_name_jp, dashboard_name_th,
                description, owner_user_id, created_by, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING bi_dashboard_id";
        $stmt = $this->db->query($sql, [$name, $nameJp, $nameTh, $desc, $user['user_id'], $user['user_id'], $user['user_id']]);
        $row = $stmt->fetch();
        $id = $row['bi_dashboard_id'];

        $this->redirect("/bi/dashboards/{$id}/edit");
    }

    // ── Show (view mode) ──
    public function show($id)
    {
        $this->requireAuth();
        $dashboard = $this->loadDashboard($id);
        if (!$dashboard) {
            flash('error', 'Dashboard not found.');
            $this->redirect('/bi/dashboards');
        }

        $widgets = $this->db->fetchAll(
            "SELECT * FROM bi_widgets WHERE bi_dashboard_id = ? AND is_deleted = FALSE ORDER BY sort_order, grid_y, grid_x",
            [$id]
        );

        $this->render('bi/view', [
            'pageTitle' => $dashboard['dashboard_name'],
            'dashboard' => $dashboard,
            'widgets'   => $widgets,
        ]);
    }

    // ── Edit (builder mode) ──
    public function edit($id)
    {
        $this->requireAuth();
        $dashboard = $this->loadDashboard($id);
        if (!$dashboard) {
            flash('error', 'Dashboard not found.');
            $this->redirect('/bi/dashboards');
        }

        $user = $this->getCurrentUser();
        if ((int)$dashboard['owner_user_id'] !== (int)$user['user_id'] && !Auth::isAdmin()) {
            flash('error', 'Access denied.');
            $this->redirect('/bi/dashboards');
        }

        $widgets = $this->db->fetchAll(
            "SELECT * FROM bi_widgets WHERE bi_dashboard_id = ? AND is_deleted = FALSE ORDER BY sort_order, grid_y, grid_x",
            [$id]
        );

        $this->render('bi/builder', [
            'pageTitle' => __('bi_edit_dashboard'),
            'dashboard' => $dashboard,
            'widgets'   => $widgets,
            'isNew'     => false,
        ]);
    }

    // ── Update Dashboard Meta ──
    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $dashboard = $this->loadDashboard($id);
        if (!$dashboard) {
            $this->json(['error' => 'Not found'], 404);
        }

        $user = $this->getCurrentUser();
        $name   = sanitize($this->input('dashboard_name', $dashboard['dashboard_name']));
        $nameJp = sanitize($this->input('dashboard_name_jp', ''));
        $nameTh = sanitize($this->input('dashboard_name_th', ''));
        $desc   = sanitize($this->input('description', ''));
        $shared = $this->input('is_shared') ? true : false;

        $this->db->query(
            "UPDATE bi_dashboards SET dashboard_name = ?, dashboard_name_jp = ?, dashboard_name_th = ?,
             description = ?, is_shared = ?, updated_by = ?, updated_at = NOW()
             WHERE bi_dashboard_id = ?",
            [$name, $nameJp, $nameTh, $desc, $shared ? 'true' : 'false', $user['user_id'], $id]
        );

        flash('success', __('bi_saved'));
        $this->redirect("/bi/dashboards/{$id}/edit");
    }

    // ── Delete Dashboard ──
    public function delete($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $dashboard = $this->loadDashboard($id);
        if (!$dashboard) {
            flash('error', 'Not found.');
            $this->redirect('/bi/dashboards');
        }

        $user = $this->getCurrentUser();
        if ((int)$dashboard['owner_user_id'] !== (int)$user['user_id'] && !Auth::isAdmin()) {
            flash('error', 'Access denied.');
            $this->redirect('/bi/dashboards');
        }

        $this->db->query(
            "UPDATE bi_dashboards SET is_deleted = TRUE, updated_by = ?, updated_at = NOW() WHERE bi_dashboard_id = ?",
            [$user['user_id'], $id]
        );

        flash('success', __('bi_deleted'));
        $this->redirect('/bi/dashboards');
    }

    // ════════════════════════════════════════════════════════════
    //  API Endpoints (JSON)
    // ════════════════════════════════════════════════════════════

    // ── GET /api/bi/schema ──
    public function apiSchema()
    {
        $this->requireAuth();
        $tables = $this->schema['tables'];
        $result = [];

        foreach ($tables as $key => $def) {
            if (!Auth::canAccess($def['access_section'])) {
                continue;
            }
            $cols = [];
            foreach ($def['columns'] as $colKey => $colDef) {
                $cols[$colKey] = [
                    'type'         => $colDef['type'],
                    'label'        => __($colDef['label_key']),
                    'aggregatable' => $colDef['aggregatable'] ?? false,
                    'groupable'    => $colDef['groupable'] ?? false,
                ];
            }

            $joins = [];
            foreach (($def['joins'] ?? []) as $jKey => $jDef) {
                $jCols = [];
                foreach ($jDef['columns'] as $jcKey => $jcDef) {
                    $jCols[$jcKey] = [
                        'type'  => $jcDef['type'],
                        'label' => __($jcDef['label_key']),
                    ];
                }
                $joins[$jKey] = ['columns' => $jCols];
            }

            $result[$key] = [
                'label'   => __($def['label_key']),
                'columns' => $cols,
                'joins'   => $joins,
            ];
        }

        $this->json([
            'tables'          => $result,
            'aggregates'      => $this->schema['aggregates'],
            'operators'       => $this->schema['operators'],
            'date_transforms' => $this->schema['date_transforms'],
        ]);
    }

    // ── POST /api/bi/query ──
    public function apiQueryData()
    {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }

        $sourceTable = $input['source_table'] ?? '';
        $tableDef = $this->schema['tables'][$sourceTable] ?? null;
        if (!$tableDef) {
            $this->json(['error' => 'Invalid table'], 400);
        }
        if (!Auth::canAccess($tableDef['access_section'])) {
            $this->json(['error' => 'Access denied'], 403);
        }

        $xAxis    = $input['x_axis'] ?? null;
        $yAxis    = $input['y_axis'] ?? null;
        $series   = $input['series'] ?? null;
        $filters  = $input['filters'] ?? [];
        $reqJoins = $input['joins'] ?? [];

        // Validate columns
        if ($xAxis && !$this->isValidColumn($sourceTable, $xAxis['column'])) {
            $this->json(['error' => 'Invalid x_axis column'], 400);
        }
        if ($yAxis && !$this->isValidColumn($sourceTable, $yAxis['column'])) {
            $this->json(['error' => 'Invalid y_axis column'], 400);
        }
        if ($series && !$this->isValidColumn($sourceTable, $series['column'])) {
            $this->json(['error' => 'Invalid series column'], 400);
        }

        // Validate aggregate
        $aggregate = strtoupper($yAxis['aggregate'] ?? 'COUNT');
        if (!in_array($aggregate, $this->schema['aggregates'])) {
            $this->json(['error' => 'Invalid aggregate'], 400);
        }

        // Build SQL
        $alias = 't';
        $params = [];
        $selectParts = [];
        $groupByParts = [];
        $joinClauses = [];

        // X-axis
        if ($xAxis) {
            $xCol = "{$alias}.{$xAxis['column']}";
            $transform = $xAxis['transform'] ?? null;
            if ($transform && in_array($transform, $this->schema['date_transforms'])) {
                $xExpr = "DATE_TRUNC('{$transform}', {$xCol})";
            } else {
                $xExpr = $xCol;
            }
            $selectParts[] = "{$xExpr} AS label";
            $groupByParts[] = $xExpr;
        }

        // Y-axis
        if ($yAxis) {
            $yCol = "{$alias}.{$yAxis['column']}";
            if ($aggregate === 'COUNT') {
                $selectParts[] = "COUNT(*) AS value";
            } else {
                $selectParts[] = "{$aggregate}({$yCol}) AS value";
            }
        } else {
            $selectParts[] = "COUNT(*) AS value";
        }

        // Series
        if ($series) {
            $sCol = $this->resolveColumnRef($alias, $sourceTable, $series['column'], $joinClauses, $reqJoins);
            $selectParts[] = "{$sCol} AS series";
            $groupByParts[] = $sCol;
        }

        // JOINs
        foreach ($reqJoins as $joinKey) {
            $this->addJoin($alias, $sourceTable, $joinKey, $joinClauses);
        }

        // WHERE
        $whereParts = ["{$alias}.is_deleted = FALSE"];
        foreach ($filters as $f) {
            $fCol = $f['column'] ?? '';
            if (!$this->isValidColumn($sourceTable, $fCol)) continue;

            $fOp = strtoupper($f['operator'] ?? '=');
            if (!in_array($fOp, $this->schema['operators'])) continue;

            $fVal = $f['value'] ?? null;
            $colRef = "{$alias}.{$fCol}";

            if ($fOp === 'IN' || $fOp === 'NOT IN') {
                if (!is_array($fVal) || empty($fVal)) continue;
                $placeholders = implode(',', array_fill(0, count($fVal), '?'));
                $whereParts[] = "{$colRef} {$fOp} ({$placeholders})";
                foreach ($fVal as $v) $params[] = $v;
            } elseif ($fOp === 'BETWEEN') {
                if (!is_array($fVal) || count($fVal) < 2) continue;
                $whereParts[] = "{$colRef} BETWEEN ? AND ?";
                $params[] = $fVal[0];
                $params[] = $fVal[1];
            } elseif ($fOp === 'LIKE') {
                $whereParts[] = "{$colRef} LIKE ?";
                $params[] = $fVal;
            } else {
                $whereParts[] = "{$colRef} {$fOp} ?";
                $params[] = $fVal;
            }
        }

        $sql = "SELECT " . implode(', ', $selectParts)
             . " FROM {$sourceTable} {$alias}";
        foreach ($joinClauses as $jc) {
            $sql .= " {$jc}";
        }
        $sql .= " WHERE " . implode(' AND ', $whereParts);
        if (!empty($groupByParts)) {
            $sql .= " GROUP BY " . implode(', ', $groupByParts);
        }
        if ($xAxis) {
            $sql .= " ORDER BY label";
        }
        $sql .= " LIMIT " . (int)$this->schema['max_rows'];

        $rows = $this->db->fetchAll($sql, $params);

        // Format for Chart.js
        $chartData = $this->formatChartData($rows, $series !== null, $xAxis);
        $this->json($chartData);
    }

    // ── POST /api/bi/widgets ──
    public function apiSaveWidget()
    {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }

        $user = $this->getCurrentUser();
        $widgetId    = $input['bi_widget_id'] ?? null;
        $dashboardId = (int)($input['bi_dashboard_id'] ?? 0);
        $widgetName  = sanitize($input['widget_name'] ?? 'Widget');
        $chartType   = sanitize($input['chart_type'] ?? 'bar');
        $dataConfig  = json_encode($input['data_config'] ?? []);
        $chartOpts   = json_encode($input['chart_options'] ?? []);
        $gridX = (int)($input['grid_x'] ?? 0);
        $gridY = (int)($input['grid_y'] ?? 0);
        $gridW = (int)($input['grid_w'] ?? 6);
        $gridH = (int)($input['grid_h'] ?? 4);

        if ($widgetId) {
            $this->db->query(
                "UPDATE bi_widgets SET widget_name = ?, chart_type = ?, data_config = ?::jsonb,
                 chart_options = ?::jsonb, grid_x = ?, grid_y = ?, grid_w = ?, grid_h = ?, updated_at = NOW()
                 WHERE bi_widget_id = ?",
                [$widgetName, $chartType, $dataConfig, $chartOpts, $gridX, $gridY, $gridW, $gridH, $widgetId]
            );
            $this->json(['bi_widget_id' => $widgetId, 'status' => 'updated']);
        } else {
            $sql = "INSERT INTO bi_widgets (bi_dashboard_id, widget_name, chart_type, data_config,
                    chart_options, grid_x, grid_y, grid_w, grid_h)
                    VALUES (?, ?, ?, ?::jsonb, ?::jsonb, ?, ?, ?, ?) RETURNING bi_widget_id";
            $stmt = $this->db->query($sql, [
                $dashboardId, $widgetName, $chartType, $dataConfig, $chartOpts,
                $gridX, $gridY, $gridW, $gridH
            ]);
            $row = $stmt->fetch();
            $this->json(['bi_widget_id' => $row['bi_widget_id'], 'status' => 'created']);
        }
    }

    // ── POST /api/bi/widgets/{id}/delete ──
    public function apiDeleteWidget($id)
    {
        $this->requireAuth();
        $this->db->query(
            "UPDATE bi_widgets SET is_deleted = TRUE, updated_at = NOW() WHERE bi_widget_id = ?",
            [$id]
        );
        $this->json(['status' => 'deleted']);
    }

    // ── POST /api/bi/dashboards/{id}/layout ──
    public function apiSaveLayout($id)
    {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !is_array($input['widgets'] ?? null)) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }

        $this->db->beginTransaction();
        try {
            foreach ($input['widgets'] as $w) {
                $this->db->query(
                    "UPDATE bi_widgets SET grid_x = ?, grid_y = ?, grid_w = ?, grid_h = ?, updated_at = NOW()
                     WHERE bi_widget_id = ? AND bi_dashboard_id = ?",
                    [(int)$w['grid_x'], (int)$w['grid_y'], (int)$w['grid_w'], (int)$w['grid_h'],
                     (int)$w['bi_widget_id'], (int)$id]
                );
            }
            $this->db->commit();
            $this->json(['status' => 'saved']);
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->json(['error' => 'Failed to save layout'], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    //  Helpers
    // ════════════════════════════════════════════════════════════

    private function loadDashboard($id)
    {
        return $this->db->fetch(
            "SELECT * FROM bi_dashboards WHERE bi_dashboard_id = ? AND is_deleted = FALSE",
            [$id]
        );
    }

    private function isValidColumn($table, $column)
    {
        $def = $this->schema['tables'][$table] ?? null;
        if (!$def) return false;
        if (isset($def['columns'][$column])) return true;
        // Check join columns
        foreach (($def['joins'] ?? []) as $jDef) {
            if (isset($jDef['columns'][$column])) return true;
        }
        return false;
    }

    private function resolveColumnRef($alias, $table, $column, &$joinClauses, $reqJoins)
    {
        $def = $this->schema['tables'][$table];
        if (isset($def['columns'][$column])) {
            return "{$alias}.{$column}";
        }
        foreach (($def['joins'] ?? []) as $jKey => $jDef) {
            if (isset($jDef['columns'][$column])) {
                $this->addJoin($alias, $table, $jKey, $joinClauses);
                return "j_{$jKey}.{$column}";
            }
        }
        return "{$alias}.{$column}";
    }

    private function addJoin($alias, $table, $joinKey, &$joinClauses)
    {
        $jDef = $this->schema['tables'][$table]['joins'][$joinKey] ?? null;
        if (!$jDef) return;
        $jAlias = "j_{$joinKey}";
        $clause = "LEFT JOIN {$jDef['table']} {$jAlias} ON {$alias}.{$jDef['fk']} = {$jAlias}.{$jDef['pk']}";
        if (!in_array($clause, $joinClauses)) {
            $joinClauses[] = $clause;
        }
    }

    private function formatChartData($rows, $hasSeries, $xAxis)
    {
        if (empty($rows)) {
            return ['labels' => [], 'datasets' => [['label' => 'Value', 'data' => []]]];
        }

        if (!$hasSeries) {
            $labels = [];
            $data = [];
            foreach ($rows as $r) {
                $lbl = $r['label'] ?? '';
                if ($xAxis && ($xAxis['transform'] ?? null)) {
                    $lbl = $this->formatDateLabel($lbl, $xAxis['transform']);
                }
                $labels[] = $lbl;
                $data[] = (float)($r['value'] ?? 0);
            }
            return [
                'labels'   => $labels,
                'datasets' => [['label' => 'Value', 'data' => $data]],
            ];
        }

        // Pivot: group by label, split by series
        $labelSet = [];
        $seriesSet = [];
        $pivot = [];

        foreach ($rows as $r) {
            $lbl = $r['label'] ?? '';
            if ($xAxis && ($xAxis['transform'] ?? null)) {
                $lbl = $this->formatDateLabel($lbl, $xAxis['transform']);
            }
            $s = $r['series'] ?? 'Other';
            $labelSet[$lbl] = true;
            $seriesSet[$s] = true;
            $pivot[$lbl][$s] = (float)($r['value'] ?? 0);
        }

        $labels = array_keys($labelSet);
        $datasets = [];
        foreach (array_keys($seriesSet) as $s) {
            $data = [];
            foreach ($labels as $lbl) {
                $data[] = $pivot[$lbl][$s] ?? 0;
            }
            $datasets[] = ['label' => $s, 'data' => $data];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    private function formatDateLabel($val, $transform)
    {
        if (!$val) return '';
        $ts = strtotime($val);
        if ($ts === false) return $val;
        switch ($transform) {
            case 'day':     return date('Y-m-d', $ts);
            case 'week':    return date('Y-\\WW', $ts);
            case 'month':   return date('Y-m', $ts);
            case 'quarter': return date('Y', $ts) . '-Q' . ceil(date('n', $ts) / 3);
            case 'year':    return date('Y', $ts);
            default:        return date('Y-m-d', $ts);
        }
    }
}
