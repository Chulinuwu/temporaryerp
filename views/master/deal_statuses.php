<?php
/**
 * PEGASUS ERP - Deal Status Master
 * Variables: $statuses
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('deal_statuses') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('deal_statuses') ?></span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openModal()"><?= _e('new_deal_status') ?></button>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px;"><?= _e('sort_order') ?></th>
                <th><?= _e('status_name') ?></th>
                <th><?= _e('name_jp') ?></th>
                <th><?= _e('name_th') ?></th>
                <th class="text-center" style="width:80px;"><?= _e('win_percentage') ?></th>
                <th class="text-center" style="width:60px;"><?= _e('color') ?></th>
                <th class="text-center" style="width:100px;"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($statuses)): ?>
                <?php foreach ($statuses as $s): ?>
                    <tr>
                        <td class="text-center"><?= e($s['sort_order']) ?></td>
                        <td><strong><?= e($s['status_name']) ?></strong></td>
                        <td><?= e($s['status_name_jp'] ?? '') ?></td>
                        <td><?= e($s['status_name_th'] ?? '') ?></td>
                        <td class="text-center"><?= e($s['win_pct']) ?>%</td>
                        <td class="text-center">
                            <span style="display:inline-block;width:24px;height:24px;border-radius:4px;background:<?= e($s['color'] ?? '#757575') ?>;"></span>
                        </td>
                        <td class="text-center actions" style="white-space:nowrap;">
                            <button onclick="editItem(<?= e(json_encode($s)) ?>)" style="background:none;border:none;cursor:pointer;" title="<?= __('edit') ?>">&#9998;</button>
                            <form method="POST" action="/master/deal-statuses/<?= e($s['status_id']) ?>/delete" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')" style="background:none;border:none;cursor:pointer;" title="<?= __('delete') ?>">&#128465;</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center" style="padding:24px;color:#9E9E9E;"><?= _e('no_data') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal-overlay" id="formModal">
    <div class="modal" style="width:600px;">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle"><?= _e('new_deal_status') ?></div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="/master/deal-statuses" id="itemForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="status_id" id="f_status_id" value="">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= _e('status_name') ?> <span class="required">*</span></label>
                        <input type="text" name="status_name" id="f_status_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('win_percentage') ?></label>
                        <input type="number" name="win_pct" id="f_win_pct" class="form-input" min="0" max="100" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('name_jp') ?></label>
                        <input type="text" name="status_name_jp" id="f_status_name_jp" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('name_th') ?></label>
                        <input type="text" name="status_name_th" id="f_status_name_th" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('sort_order') ?></label>
                        <input type="number" name="sort_order" id="f_sort_order" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('color') ?></label>
                        <input type="color" name="color" id="f_color" value="#757575" style="height:38px;width:100%;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal()"><?= _e('cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').textContent = '<?= __('new_deal_status') ?>';
    document.getElementById('itemForm').reset();
    document.getElementById('f_status_id').value = '';
    document.getElementById('formModal').classList.add('active');
}
function closeModal() { document.getElementById('formModal').classList.remove('active'); }
function editItem(d) {
    document.getElementById('modalTitle').textContent = '<?= __('edit') ?>';
    document.getElementById('f_status_id').value = d.status_id || '';
    document.getElementById('f_status_name').value = d.status_name || '';
    document.getElementById('f_status_name_jp').value = d.status_name_jp || '';
    document.getElementById('f_status_name_th').value = d.status_name_th || '';
    document.getElementById('f_win_pct').value = d.win_pct || 0;
    document.getElementById('f_sort_order').value = d.sort_order || 0;
    document.getElementById('f_color').value = d.color || '#757575';
    document.getElementById('formModal').classList.add('active');
}
document.getElementById('formModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
</script>
