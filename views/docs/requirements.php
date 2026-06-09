<?php
/**
 * PEGASUS ERP - System Requirements View
 *
 * Usage:
 *   In your controller:
 *     $viewData = [
 *         'pageTitle' => 'System Requirements',
 *     ];
 *     render('docs/requirements', $viewData);
 *
 * Variables (all optional — defaults provided below):
 *   $nonFunctional  array  Non-functional requirements grouped by category
 *   $techStack      array  Technology stack candidates grouped by layer
 *   $roadmap        array  Implementation phases
 */
extract($viewData ?? []);

$nonFunctional = $nonFunctional ?? [
    'security' => [
        'icon'  => '&#128274;',
        'title' => 'セキュリティ',
        'items' => [
            ['text' => 'HTTPS通信必須（SSL/TLS）',             'badge' => 'required'],
            ['text' => 'パスワードはbcrypt等でハッシュ化保存', 'badge' => 'required'],
            ['text' => 'セッション管理（タイムアウト設定）',    'badge' => null],
            ['text' => 'CSRF対策',                              'badge' => null],
            ['text' => 'ロールベースアクセス制御（RBAC）',      'badge' => null],
        ],
    ],
    'performance' => [
        'icon'  => '&#9889;',
        'title' => 'パフォーマンス',
        'table' => [
            ['対象' => '出勤簿一覧 表示',     '目標値' => '&lt; 2秒'],
            ['対象' => '打刻記録 レスポンス', '目標値' => '&lt; 1秒'],
            ['対象' => '月次集計データ',      '目標値' => 'キャッシュ活用'],
        ],
    ],
    'availability' => [
        'icon'  => '&#128737;',
        'title' => '可用性',
        'items' => [
            ['text' => '稼働率 99.9% 以上（月間ダウンタイム &lt; 44分）', 'badge' => null],
            ['text' => 'データバックアップ: 日次',                         'badge' => null],
        ],
    ],
    'i18n' => [
        'icon'  => '&#127760;',
        'title' => '多言語対応',
        'items' => [
            ['text' => 'i18n対応: 日本語・英語を最低限サポート', 'badge' => null],
            ['text' => 'UIテキストはロケールファイルで管理',     'badge' => null],
        ],
    ],
    'responsive' => [
        'icon'  => '&#128241;',
        'title' => 'レスポンシブ対応',
        'items' => [
            ['text' => 'PC / タブレット / スマートフォン対応', 'badge' => null],
            ['text' => 'モバイルからの打刻操作を優先考慮',      'badge' => null],
        ],
    ],
];

$techStack = $techStack ?? [
    'frontend' => [
        'icon'  => '&#127912;',
        'title' => 'フロントエンド',
        'rows'  => [
            ['項目' => 'フレームワーク',  '候補' => 'Next.js / Nuxt.js'],
            ['項目' => 'UIライブラリ',    '候補' => 'Tailwind + shadcn/ui'],
            ['項目' => '状態管理',        '候補' => 'Zustand / Pinia'],
            ['項目' => '日付操作',        '候補' => 'Day.js'],
            ['項目' => 'テーブル',        '候補' => 'TanStack Table'],
        ],
    ],
    'backend' => [
        'icon'  => '&#9881;',
        'title' => 'バックエンド',
        'rows'  => [
            ['項目' => 'フレームワーク',    '候補' => 'PHP (Laravel / Slim)'],
            ['項目' => '認証',              '候補' => 'Session + CSRF / JWT'],
            ['項目' => 'バリデーション',    '候補' => 'Respect\Validation'],
            ['項目' => 'ORM',               '候補' => 'Eloquent / PDO'],
        ],
    ],
    'database' => [
        'icon'  => '&#128451;',
        'title' => 'データベース',
        'rows'  => [
            ['項目' => 'メインDB',   '候補' => 'PostgreSQL / MySQL'],
            ['項目' => 'キャッシュ', '候補' => 'Redis'],
            ['項目' => 'ファイル',   '候補' => 'ローカル / S3互換'],
        ],
    ],
    'infra' => [
        'icon'  => '&#9729;',
        'title' => 'インフラ',
        'rows'  => [
            ['項目' => 'Webサーバ',    '候補' => 'Apache / Nginx + PHP-FPM'],
            ['項目' => 'コンテナ',     '候補' => 'Docker / Docker Compose'],
            ['項目' => 'CI/CD',        '候補' => 'GitHub Actions'],
        ],
    ],
];

$roadmap = $roadmap ?? [
    ['phase' => 1, 'title' => 'MVP（最小構成）',  'color' => 'green',  'items' => ['ユーザー認証（ログイン・ログアウト）','打刻機能（出勤・退勤）','出勤簿表示（月次）','基本集計（労働時間・残業時間）']],
    ['phase' => 2, 'title' => '基本機能',          'color' => 'blue',   'items' => ['打刻修正申請','休暇申請・承認フロー','残業申請・承認フロー','スタッフ設定']],
    ['phase' => 3, 'title' => '発展機能',          'color' => 'purple', 'items' => ['工数管理','シフト管理','管理者ダッシュボード','PDF/CSVエクスポート']],
    ['phase' => 4, 'title' => '拡張機能',          'color' => 'orange', 'items' => ['多言語対応','モバイル最適化','外部システム連携（給与計算等）','通知機能（メール・Slack）']],
];

/* Fallbacks for environments where these helpers are not loaded */
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('_e')) {
    function _e($k) { return htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8'); }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">システム要件定義</h1>
        <div class="breadcrumb">
            <a href="/dashboard">ダッシュボード</a>
            <span class="breadcrumb-sep">/</span>
            <span class="breadcrumb-current">システム要件定義</span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="#sec6" class="btn btn-cancel">6. 非機能要件</a>
        <a href="#sec7" class="btn btn-cancel">7. 技術スタック</a>
        <a href="#sec-roadmap" class="btn btn-primary">ロードマップ</a>
    </div>
</div>

<!-- ===================== Section 6: Non-Functional Requirements ===================== -->
<div id="sec6" class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h2 class="card-title">
            <span class="section-num">6</span>
            非機能要件
        </h2>
    </div>
    <div class="card-body">
        <div class="req-grid">
            <?php foreach ($nonFunctional as $key => $group): ?>
                <div class="req-block">
                    <h3 class="req-title">
                        <span class="req-icon"><?= $group['icon'] ?></span>
                        <?= e($group['title']) ?>
                    </h3>

                    <?php if (!empty($group['table'])): ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($group['table'][0]) as $col): ?>
                                            <th><?= e($col) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['table'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $cell): ?>
                                                <td><?= $cell /* allow &lt; entity */ ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($group['items'])): ?>
                        <ul class="req-list">
                            <?php foreach ($group['items'] as $item): ?>
                                <li>
                                    <span><?= $item['text'] /* allow &lt; entity */ ?></span>
                                    <?php if (!empty($item['badge'])): ?>
                                        <span class="badge-<?= e($item['badge']) ?>"><?= $item['badge'] === 'required' ? '必須' : 'OPT' ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===================== Section 7: Technology Stack ===================== -->
<div id="sec7" class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h2 class="card-title">
            <span class="section-num">7</span>
            技術スタック案
        </h2>
    </div>
    <div class="card-body">
        <div class="stack-grid">
            <?php foreach ($techStack as $key => $layer): ?>
                <div class="stack-card">
                    <div class="stack-card-head">
                        <span class="stack-icon"><?= $layer['icon'] ?></span>
                        <span class="stack-title"><?= e($layer['title']) ?></span>
                    </div>
                    <table class="stack-table">
                        <tbody>
                            <?php foreach ($layer['rows'] as $row): ?>
                                <tr>
                                    <th><?= e($row['項目']) ?></th>
                                    <td><?= e($row['候補']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===================== Appendix: Roadmap ===================== -->
<div id="sec-roadmap" class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h2 class="card-title">
            <span class="section-num">&#9733;</span>
            付録: 実装優先度ロードマップ
        </h2>
    </div>
    <div class="card-body">
        <div class="phase-grid">
            <?php foreach ($roadmap as $phase): ?>
                <div class="phase-card phase-<?= e($phase['color']) ?>">
                    <div class="phase-head">
                        <span class="phase-label">Phase <?= (int) $phase['phase'] ?></span>
                        <span class="phase-title"><?= e($phase['title']) ?></span>
                    </div>
                    <ol class="phase-items">
                        <?php foreach ($phase['items'] as $item): ?>
                            <li><?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===================== Page-scoped styles ===================== -->
<style>
.section-num {
    display:inline-flex;align-items:center;justify-content:center;
    width:26px;height:26px;border-radius:50%;
    background:var(--color-primary);color:#fff;
    font-size:12px;font-weight:700;margin-right:8px;
}

/* Non-functional requirements */
.req-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));
    gap:16px;
}
.req-block {
    background:var(--color-sidebar-bg);
    border:1px solid var(--color-border);
    border-radius:6px;
    padding:14px 16px;
}
.req-title {
    display:flex;align-items:center;gap:8px;
    font-size:14px;font-weight:600;
    color:var(--color-primary-dark);
    margin-bottom:10px;padding-bottom:8px;
    border-bottom:1px solid var(--color-border-light);
}
.req-icon { font-size:18px; }
.req-list { list-style:none; padding:0; margin:0; }
.req-list li {
    display:flex;justify-content:space-between;align-items:center;
    padding:6px 10px;margin-bottom:4px;
    background:#fff;border-radius:4px;
    border-left:3px solid var(--color-success);
    font-size:13px;
}
.badge-required {
    padding:2px 8px;border-radius:3px;
    background:var(--color-danger);color:#fff;
    font-size:11px;font-weight:600;
}

/* Tech stack */
.stack-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
    gap:16px;
}
.stack-card {
    background:#fff;
    border:1px solid var(--color-border);
    border-top:3px solid var(--color-primary);
    border-radius:6px;
    overflow:hidden;
    transition:transform .15s, box-shadow .15s;
}
.stack-card:hover {
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}
.stack-card-head {
    display:flex;align-items:center;gap:8px;
    padding:10px 14px;
    background:var(--color-primary-light);
    border-bottom:1px solid var(--color-border-light);
}
.stack-icon { font-size:18px; }
.stack-title {
    font-weight:600;color:var(--color-primary-dark);
    font-size:13px;
}
.stack-table { width:100%; font-size:13px; }
.stack-table th, .stack-table td {
    padding:8px 14px;border-bottom:1px solid var(--color-border-light);
    text-align:left;
}
.stack-table th {
    background:transparent;color:var(--color-text-secondary);
    font-weight:500;width:40%;
}
.stack-table tr:last-child th,
.stack-table tr:last-child td { border-bottom:none; }

/* Roadmap phases */
.phase-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));
    gap:16px;
}
.phase-card {
    border-radius:6px;padding:14px 16px;
    color:#fff;box-shadow:0 2px 4px rgba(0,0,0,.08);
}
.phase-green  { background:linear-gradient(135deg, #43A047, #2E7D32); }
.phase-blue   { background:linear-gradient(135deg, #1976D2, #1565C0); }
.phase-purple { background:linear-gradient(135deg, #8E24AA, #6A1B9A); }
.phase-orange { background:linear-gradient(135deg, #FB8C00, #EF6C00); }

.phase-head {
    display:flex;align-items:center;gap:8px;
    padding-bottom:8px;margin-bottom:10px;
    border-bottom:1px solid rgba(255,255,255,.3);
}
.phase-label {
    padding:2px 8px;border-radius:10px;
    background:rgba(255,255,255,.22);
    font-size:11px;font-weight:700;letter-spacing:.5px;
}
.phase-title { font-size:14px;font-weight:600; }
.phase-items { padding-left:18px;font-size:13px; }
.phase-items li { margin-bottom:4px; }

/* Breadcrumb */
.breadcrumb-sep { margin:0 6px;color:var(--color-text-muted); }

@media (max-width: 640px) {
    .page-header { flex-direction:column;align-items:flex-start;gap:10px; }
}
</style>
