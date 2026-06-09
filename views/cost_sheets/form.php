<?php
/**
 * PEGASUS ERP - Cost Sheet Create Form (with Excel Import)
 */
$cs = $sheet;
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= __('new_cost_sheet') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/cost-sheets"><?= __('cost_sheet_list') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= __('new_cost_sheet') ?></span>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:1100px;">

    <!-- Left: Manual Create -->
    <div class="card">
        <div style="padding:14px 20px;border-bottom:1px solid var(--color-border);">
            <h3 style="margin:0;font-size:14px;">&#128221; <?= __('manual_create') ?></h3>
        </div>
        <div class="card-body" style="padding:24px;">
            <form method="POST" action="/cost-sheets">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label class="form-label"><?= __('cost_sheet_no') ?></label>
                    <input type="text" name="sheet_no" class="form-input" placeholder="<?= __('auto_generate') ?>">
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;"><?= __('auto_generate') ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('cost_sheet_name') ?> *</label>
                    <input type="text" name="sheet_name" class="form-input" required
                           placeholder="e.g. Estimate cost for Project ABC">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('customer') ?></label>
                    <select name="customer_id" class="form-input">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e($c['customer_id']) ?>"><?= e($c['customer_name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('notes') ?></label>
                    <textarea name="notes" class="form-input" rows="2"></textarea>
                </div>

                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                    <a href="/cost-sheets" class="btn btn-cancel"><?= __('cancel') ?></a>
                    <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Excel Import -->
    <div class="card">
        <div style="padding:14px 20px;border-bottom:1px solid var(--color-border);">
            <h3 style="margin:0;font-size:14px;">&#128196; <?= __('import_excel') ?></h3>
        </div>
        <div class="card-body" style="padding:24px;">
            <form method="POST" action="/cost-sheets/import-new" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label class="form-label"><?= __('cost_sheet_name') ?> *</label>
                    <input type="text" name="sheet_name" id="import-sheet-name" class="form-input" required
                           placeholder="<?= __('auto_from_file') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('customer') ?></label>
                    <select name="customer_id" class="form-input">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e($c['customer_id']) ?>"><?= e($c['customer_name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('excel_file') ?> (.xlsx) *</label>
                    <div id="drop-zone" style="border:2px dashed var(--color-border);border-radius:8px;padding:30px 20px;text-align:center;cursor:pointer;transition:all 0.2s;"
                         onclick="document.getElementById('file-input').click();"
                         ondragover="event.preventDefault();this.style.borderColor='var(--color-primary)';this.style.background='#f0f4ff';"
                         ondragleave="this.style.borderColor='var(--color-border)';this.style.background='none';"
                         ondrop="event.preventDefault();this.style.borderColor='var(--color-border)';this.style.background='none';handleFile(event.dataTransfer.files[0]);">
                        <div id="drop-icon" style="font-size:36px;margin-bottom:8px;">&#128451;</div>
                        <div id="drop-text" style="font-size:13px;color:var(--color-text-muted);">
                            <?= __('drop_excel_here') ?>
                        </div>
                        <div id="file-name" style="font-size:13px;font-weight:600;color:var(--color-primary);display:none;margin-top:8px;"></div>
                    </div>
                    <input type="file" name="cost_file" id="file-input" accept=".xlsx,.xls"
                           style="display:none;" onchange="handleFile(this.files[0]);" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('excel_sheet_name') ?></label>
                    <input type="text" name="sheet_name_excel" class="form-input"
                           placeholder="<?= __('auto_detect') ?> (e.g. 001_Breakdown cost)">
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;"><?= __('auto_detect_hint') ?></div>
                </div>

                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                    <a href="/cost-sheets" class="btn btn-cancel"><?= __('cancel') ?></a>
                    <button type="submit" class="btn btn-primary" id="btn-import" disabled>
                        &#128196; <?= __('create_and_import') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column mapping reference -->
<div class="card" style="margin-top:20px;max-width:1100px;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--color-border);">
        <h4 style="margin:0;font-size:13px;color:var(--color-text-muted);">&#9432; <?= __('column_mapping') ?></h4>
    </div>
    <div style="padding:12px 20px;">
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <tr style="background:#f5f7fa;">
                <th style="padding:6px 10px;text-align:left;">Excel</th>
                <th style="padding:6px 10px;text-align:left;">D</th>
                <th style="padding:6px 10px;text-align:left;">E</th>
                <th style="padding:6px 10px;text-align:left;">F</th>
                <th style="padding:6px 10px;text-align:left;">G</th>
                <th style="padding:6px 10px;text-align:left;">H</th>
                <th style="padding:6px 10px;text-align:left;">J</th>
                <th style="padding:6px 10px;text-align:left;">K</th>
                <th style="padding:6px 10px;text-align:left;">L</th>
                <th style="padding:6px 10px;text-align:left;">M</th>
                <th style="padding:6px 10px;text-align:left;">N</th>
            </tr>
            <tr>
                <td style="padding:6px 10px;font-weight:600;"><?= __('mapped_to') ?></td>
                <td style="padding:6px 10px;"><?= __('cost_category') ?></td>
                <td style="padding:6px 10px;"><?= __('description') ?></td>
                <td style="padding:6px 10px;"><?= __('supplier') ?></td>
                <td style="padding:6px 10px;">Brand</td>
                <td style="padding:6px 10px;">Lead Time</td>
                <td style="padding:6px 10px;"><?= __('unit_price') ?></td>
                <td style="padding:6px 10px;"><?= __('quantity') ?></td>
                <td style="padding:6px 10px;"><?= __('total') ?></td>
                <td style="padding:6px 10px;"><?= __('unit') ?></td>
                <td style="padding:6px 10px;"><?= __('remark') ?></td>
            </tr>
        </table>
        <div style="font-size:11px;color:var(--color-text-muted);margin-top:8px;">
            <?= __('import_data_range') ?>
        </div>
    </div>
</div>

<script>
function handleFile(file) {
    if (!file) return;
    document.getElementById('drop-icon').innerHTML = '&#128196;';
    document.getElementById('drop-text').style.display = 'none';
    document.getElementById('file-name').style.display = 'block';
    document.getElementById('file-name').textContent = file.name;
    document.getElementById('drop-zone').style.borderColor = 'var(--color-primary)';
    document.getElementById('drop-zone').style.background = '#f0f8ff';
    document.getElementById('btn-import').disabled = false;

    // Auto-fill sheet name from file name if empty
    var nameInput = document.getElementById('import-sheet-name');
    if (!nameInput.value) {
        var name = file.name.replace(/\.[^/.]+$/, '');
        nameInput.value = name;
    }

    // Set file input
    var input = document.getElementById('file-input');
    if (input.files.length === 0) {
        var dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
    }
}
</script>
