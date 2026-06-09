<?php /** Vars: $categories */ ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= __('menu_quotation_categories') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a> <span class="breadcrumb-separator">/</span>
            <a href="/master/customers"><?= __('menu_master') ?? 'Master' ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= __('menu_quotation_categories') ?></span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openQcModal()">+ <?= __('new') ?? '新規' ?></button>
</div>

<div class="card" style="padding:0;">
<table class="data-table">
    <thead>
        <tr>
            <th style="width:90px;"><?= __('sort_order') ?? '順序' ?></th>
            <th style="width:120px;"><?= __('code') ?></th>
            <th><?= __('name_jp') ?? '名称 (日)' ?></th>
            <th><?= __('name_en') ?? '名称 (EN)' ?></th>
            <th><?= __('name_th') ?? '名称 (TH)' ?></th>
            <th style="width:120px;text-align:right;"><?= __('cost_coefficient') ?></th>
            <th><?= __('description') ?></th>
            <th style="width:80px;text-align:center;"><?= __('active') ?? '有効' ?></th>
            <th style="width:140px;text-align:center;"><?= __('actions') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($categories)): ?>
        <tr><td colspan="9" style="text-align:center;padding:40px;color:#888;"><?= __('no_data') ?></td></tr>
    <?php else: foreach ($categories as $c): ?>
        <tr>
            <td><?= (int)$c['sort_order'] ?></td>
            <td><strong><?= htmlspecialchars($c['category_code']) ?></strong></td>
            <td><?= htmlspecialchars($c['name_jp']) ?></td>
            <td><?= htmlspecialchars($c['name_en'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['name_th'] ?? '') ?></td>
            <td style="text-align:right;font-weight:600;color:#1976D2;">
                ×<?= number_format((float)($c['cost_coefficient'] ?? 1), 2) ?>
            </td>
            <td style="font-size:11px;color:#666;"><?= htmlspecialchars(mb_substr($c['description'] ?? '', 0, 60)) ?></td>
            <td style="text-align:center;"><?= $c['is_active'] ? '✓' : '—' ?></td>
            <td style="text-align:center;">
                <button class="btn btn-sm btn-cancel" onclick='editQc(<?= json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>✎</button>
                <form method="POST" action="/master/quotation-categories/<?= (int)$c['category_id'] ?>/delete" style="display:inline;"
                      onsubmit="return confirm('<?= __('confirm_delete') ?>');">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-danger">×</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<!-- Modal -->
<div class="modal-overlay" id="qcModal">
    <div class="modal" style="width:600px;">
        <div class="modal-header">
            <div class="modal-title" id="qcModalTitle"><?= __('new') ?? '新規' ?></div>
            <button class="modal-close" onclick="closeQcModal()">&times;</button>
        </div>
        <form method="POST" action="/master/quotation-categories">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="category_id" id="qc_id" value="">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= __('code') ?> <span class="required">*</span></label>
                        <input type="text" name="category_code" id="qc_code" class="form-input" required maxlength="40">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('sort_order') ?? '順序' ?></label>
                        <input type="number" name="sort_order" id="qc_sort" class="form-input" value="100">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label"><?= __('cost_coefficient') ?> <span style="color:red;">*</span>
                            <small style="color:#888;font-weight:400;"><?= __('cost_coefficient_hint') ?></small>
                        </label>
                        <input type="number" name="cost_coefficient" id="qc_coeff" class="form-input"
                               step="0.01" min="0" value="1.00" required>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label"><?= __('name_jp') ?? '名称 (日)' ?> <span class="required">*</span></label>
                        <input type="text" name="name_jp" id="qc_name_jp" class="form-input" required maxlength="120">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('name_en') ?? '名称 (EN)' ?></label>
                        <input type="text" name="name_en" id="qc_name_en" class="form-input" maxlength="120">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('name_th') ?? '名称 (TH)' ?></label>
                        <input type="text" name="name_th" id="qc_name_th" class="form-input" maxlength="120">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label"><?= __('description') ?></label>
                        <textarea name="description" id="qc_desc" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="is_active" id="qc_active" checked> <?= __('active') ?? '有効' ?></label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeQcModal()"><?= __('cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openQcModal(){
    document.getElementById('qc_id').value='';
    document.getElementById('qc_code').value='';
    document.getElementById('qc_sort').value='100';
    document.getElementById('qc_name_jp').value='';
    document.getElementById('qc_name_en').value='';
    document.getElementById('qc_name_th').value='';
    document.getElementById('qc_desc').value='';
    document.getElementById('qc_coeff').value='1.00';
    document.getElementById('qc_active').checked=true;
    document.getElementById('qcModalTitle').textContent='<?= __('new') ?? '新規' ?>';
    document.getElementById('qcModal').classList.add('active');
}
function editQc(d){
    document.getElementById('qc_id').value=d.category_id||'';
    document.getElementById('qc_code').value=d.category_code||'';
    document.getElementById('qc_sort').value=d.sort_order||0;
    document.getElementById('qc_name_jp').value=d.name_jp||'';
    document.getElementById('qc_name_en').value=d.name_en||'';
    document.getElementById('qc_name_th').value=d.name_th||'';
    document.getElementById('qc_desc').value=d.description||'';
    document.getElementById('qc_coeff').value = d.cost_coefficient || '1.00';
    document.getElementById('qc_active').checked = (d.is_active===true || d.is_active==='t' || d.is_active==='true');
    document.getElementById('qcModalTitle').textContent='<?= __('edit') ?>';
    document.getElementById('qcModal').classList.add('active');
}
function closeQcModal(){ document.getElementById('qcModal').classList.remove('active'); }
</script>
