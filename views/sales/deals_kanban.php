<?php
/**
 * PEGASUS ERP - Deal Kanban Board (案件カンバン)
 * Variables: $deals, $statuses
 */
$dealsByStatus = [];
foreach ($statuses as $s) {
    $dealsByStatus[$s['status_id']] = [];
}
foreach ($deals as $d) {
    $sid = $d['status_id'];
    if (isset($dealsByStatus[$sid])) {
        $dealsByStatus[$sid][] = $d;
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('deal_kanban') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/deals"><?= _e('deals') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('kanban_view') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/sales/deals" class="btn btn-cancel"><?= _e('list_view') ?></a>
        <a href="/sales/deals/create" class="btn btn-primary"><?= _e('new_deal') ?></a>
    </div>
</div>

<!-- Kanban Board -->
<div class="kanban-board" style="display:flex;gap:12px;overflow-x:auto;padding-bottom:16px;min-height:500px;">
    <?php foreach ($statuses as $s): ?>
        <?php
        $columnDeals = $dealsByStatus[$s['status_id']] ?? [];
        $columnTotal = array_sum(array_column($columnDeals, 'expected_amount'));
        ?>
        <div class="kanban-column" data-status-id="<?= $s['status_id'] ?>"
             style="min-width:260px;max-width:300px;flex:1;background:var(--color-bg-secondary);border-radius:8px;padding:12px;">
            <!-- Column Header -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div>
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= e($s['color']) ?>;margin-right:6px;"></span>
                    <strong style="font-size:13px;"><?= e($s['status_name']) ?></strong>
                    <span style="font-size:11px;color:var(--color-text-muted);margin-left:4px;">(<?= count($columnDeals) ?>)</span>
                </div>
                <span style="font-size:11px;color:var(--color-text-muted);"><?= $s['win_pct'] ?>%</span>
            </div>
            <div style="font-size:11px;color:var(--color-text-muted);margin-bottom:10px;">
                <?= formatMoney($columnTotal) ?>
            </div>

            <!-- Cards -->
            <div class="kanban-cards" data-status-id="<?= $s['status_id'] ?>" style="min-height:60px;">
                <?php foreach ($columnDeals as $d): ?>
                    <div class="kanban-card" data-deal-id="<?= $d['deal_id'] ?>"
                         draggable="true"
                         style="background:#fff;border-radius:6px;padding:10px 12px;margin-bottom:8px;box-shadow:0 1px 3px rgba(0,0,0,0.08);cursor:grab;border-left:3px solid <?= e($s['color']) ?>;">
                        <a href="/sales/deals/<?= e($d['deal_id']) ?>" style="font-weight:600;font-size:12px;color:var(--color-primary);text-decoration:none;">
                            <?= e($d['deal_no']) ?>
                        </a>
                        <div style="font-size:12px;font-weight:500;margin:4px 0;"><?= e($d['deal_name']) ?></div>
                        <div style="font-size:11px;color:var(--color-text-muted);"><?= e($d['customer_name'] ?? '') ?></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                            <strong style="font-size:12px;color:var(--color-primary);"><?= formatMoney($d['expected_amount'] ?? 0) ?></strong>
                            <span style="font-size:10px;color:var(--color-text-muted);"><?= e($d['sales_person_name'] ?? '') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// Kanban Drag & Drop
document.addEventListener('DOMContentLoaded', function() {
    var cards = document.querySelectorAll('.kanban-card');
    var columns = document.querySelectorAll('.kanban-cards');

    cards.forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', card.dataset.dealId);
            card.style.opacity = '0.5';
        });
        card.addEventListener('dragend', function() {
            card.style.opacity = '1';
        });
    });

    columns.forEach(function(col) {
        col.addEventListener('dragover', function(e) {
            e.preventDefault();
            col.style.background = 'rgba(25,118,210,0.05)';
        });
        col.addEventListener('dragleave', function() {
            col.style.background = '';
        });
        col.addEventListener('drop', function(e) {
            e.preventDefault();
            col.style.background = '';
            var dealId = e.dataTransfer.getData('text/plain');
            var statusId = col.dataset.statusId;
            var card = document.querySelector('.kanban-card[data-deal-id="' + dealId + '"]');
            if (card) {
                col.appendChild(card);
                // Update via API
                var formData = new FormData();
                formData.append('status_id', statusId);
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                fetch('/sales/deals/' + dealId + '/status', {
                    method: 'POST',
                    body: formData
                });
            }
        });
    });
});
</script>
