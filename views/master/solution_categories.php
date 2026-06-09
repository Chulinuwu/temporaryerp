<?php
/**
 * PEGASUS ERP - Solution Category Master
 * Variables: $categories
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('solution_categories') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('solution_categories') ?></span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openModal()"><?= _e('new_solution_category') ?></button>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px;"><?= _e('sort_order') ?></th>
                <th><?= _e('category_name') ?></th>
                <th><?= _e('name_jp') ?></th>
                <th><?= _e('name_th') ?></th>
                <th style="width:80px;"><?= _e('classification') ?></th>
                <th style="width:100px;" class="text-right"><?= _e('eval_profit_rate') ?></th>
                <th class="text-center" style="width:100px;"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td class="text-center"><?= e($c['sort_order']) ?></td>
                        <td><strong><?= e($c['category_name']) ?></strong></td>
                        <td><?= e($c['category_name_jp'] ?? '') ?></td>
                        <td><?= e($c['category_name_th'] ?? '') ?></td>
                        <td>
                            <?php
                            $cls = $c['classification'] ?? '-';
                            $clsColor = $cls === 'IT' ? '#1a73e8' : ($cls === 'OT' ? '#34a853' : ($cls === 'FA' ? '#ea8600' : '#999'));
                            ?>
                            <span style="background:<?= $clsColor ?>;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;"><?= e($cls) ?></span>
                        </td>
                        <td class="text-right"><?= e($c['eval_profit_rate'] ?? 0) ?>%</td>
                        <td class="text-center actions" style="white-space:nowrap;">
                            <button onclick="editItem(<?= e(json_encode($c)) ?>)" style="background:none;border:none;cursor:pointer;" title="<?= __('edit') ?>">&#9998;</button>
                            <form method="POST" action="/master/solution-categories/<?= e($c['category_id']) ?>/delete" style="display:inline;">
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
    <div class="modal" style="width:560px;">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle"><?= _e('new_solution_category') ?></div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="/master/solution-categories" id="itemForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="category_id" id="f_category_id" value="">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group form-full">
                        <label class="form-label"><?= _e('category_name') ?> <span class="required">*</span></label>
                        <input type="text" name="category_name" id="f_category_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('name_jp') ?></label>
                        <input type="text" name="category_name_jp" id="f_category_name_jp" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('name_th') ?></label>
                        <input type="text" name="category_name_th" id="f_category_name_th" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('classification') ?></label>
                        <select name="classification" id="f_classification" class="form-select">
                            <option value="-">-</option>
                            <option value="IT">IT</option>
                            <option value="OT">OT</option>
                            <option value="FA">FA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('eval_profit_rate') ?> (%)</label>
                        <input type="number" name="eval_profit_rate" id="f_eval_profit_rate" class="form-input" value="100" step="0.01" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('sort_order') ?></label>
                        <input type="number" name="sort_order" id="f_sort_order" class="form-input" value="0">
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
    document.getElementById('modalTitle').textContent = '<?= __('new_solution_category') ?>';
    document.getElementById('itemForm').reset();
    document.getElementById('f_category_id').value = '';
    document.getElementById('formModal').classList.add('active');
}
function closeModal() { document.getElementById('formModal').classList.remove('active'); }
function editItem(d) {
    document.getElementById('modalTitle').textContent = '<?= __('edit') ?>';
    document.getElementById('f_category_id').value = d.category_id || '';
    document.getElementById('f_category_name').value = d.category_name || '';
    document.getElementById('f_category_name_jp').value = d.category_name_jp || '';
    document.getElementById('f_category_name_th').value = d.category_name_th || '';
    document.getElementById('f_classification').value = d.classification || '-';
    document.getElementById('f_eval_profit_rate').value = d.eval_profit_rate || 0;
    document.getElementById('f_sort_order').value = d.sort_order || 0;
    document.getElementById('formModal').classList.add('active');
}
document.getElementById('formModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
</script>
