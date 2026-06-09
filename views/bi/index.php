<div class="page-header">
    <h1><?= _e('bi_dashboards') ?></h1>
    <a href="/bi/dashboards/create" class="btn btn-primary"><?= _e('bi_new_dashboard') ?></a>
</div>

<?php if (empty($dashboards)): ?>
    <div class="card" style="text-align:center; padding:60px 20px;">
        <p style="font-size:18px; color:var(--color-text-muted);"><?= _e('bi_no_dashboards') ?></p>
        <a href="/bi/dashboards/create" class="btn btn-primary" style="margin-top:16px;"><?= _e('bi_create_first') ?></a>
    </div>
<?php else: ?>
    <div class="bi-dashboard-grid">
        <?php foreach ($dashboards as $d): ?>
            <div class="card bi-dashboard-card">
                <div class="bi-dashboard-card-header">
                    <h3><?= htmlspecialchars($d['dashboard_name']) ?></h3>
                    <?php if ($d['is_shared']): ?>
                        <span class="badge badge-info"><?= _e('bi_shared') ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($d['description']): ?>
                    <p class="bi-dashboard-card-desc"><?= htmlspecialchars($d['description']) ?></p>
                <?php endif; ?>
                <div class="bi-dashboard-card-meta">
                    <span><?= _e('bi_updated') ?>: <?= formatDate($d['updated_at']) ?></span>
                </div>
                <div class="bi-dashboard-card-actions">
                    <a href="/bi/dashboards/<?= $d['bi_dashboard_id'] ?>" class="btn btn-sm"><?= _e('bi_view') ?></a>
                    <?php if ((int)$d['owner_user_id'] === (int)$userId || Auth::isAdmin()): ?>
                        <a href="/bi/dashboards/<?= $d['bi_dashboard_id'] ?>/edit" class="btn btn-sm btn-primary"><?= _e('bi_edit') ?></a>
                        <form method="POST" action="/bi/dashboards/<?= $d['bi_dashboard_id'] ?>/delete" style="display:inline;"
                              onsubmit="return confirm('<?= _e('confirm_delete') ?>');">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><?= _e('delete') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
