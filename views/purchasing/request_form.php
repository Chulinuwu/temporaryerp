<?php /** Variables: $pr (null on create), $lines, $suppliers, $projects */ ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('pr_new') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/purchasing/requests"><?= _e('menu_purchase_requests') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('pr_new') ?></span>
        </div>
    </div>
</div>

<form method="POST" action="/purchasing/requests" id="prForm" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="card" style="padding:20px;">
        <div class="form-grid" style="display:grid;grid-template-columns:repeat(3, minmax(0,1fr));gap:16px;">
            <div class="form-group">
                <label><?= _e('department') ?></label>
                <input type="text" name="department" class="form-input" maxlength="100">
            </div>
            <div class="form-group">
                <label><?= _e('needed_by') ?></label>
                <input type="date" name="needed_by_date" class="form-input">
            </div>
            <div class="form-group">
                <label><?= _e('suggested_supplier') ?></label>
                <select name="suggested_supplier_id" class="form-select">
                    <option value="">— <?= _e('none') ?> —</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int)$s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_code'] . ' / ' . $s['supplier_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= _e('project') ?? 'プロジェクト' ?></label>
                <select name="project_id" class="form-select">
                    <option value="">— <?= _e('none') ?> —</option>
                    <?php foreach (($projects ?? []) as $p): ?>
                        <option value="<?= (int)$p['project_id'] ?>">
                            <?= htmlspecialchars($p['pj_no'] . ' / ' . $p['pj_name']
                                . (!empty($p['customer_name']) ? ' (' . $p['customer_name'] . ')' : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label><?= _e('justification') ?></label>
                <textarea name="justification" class="form-input" rows="3" required></textarea>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label><?= _e('notes') ?></label>
                <textarea name="notes" class="form-input" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;padding:20px;">
        <h3 style="margin-top:0;"><?= _e('pr_items') ?></h3>
        <table class="data-table" id="linesTable">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="min-width:280px;"><?= _e('description') ?></th>
                    <th style="width:90px;"><?= _e('qty') ?></th>
                    <th style="width:80px;"><?= _e('unit') ?></th>
                    <th style="width:120px;"><?= _e('unit_price') ?></th>
                    <th style="width:130px;text-align:right;"><?= _e('line_total') ?></th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="linesBody"></tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;font-weight:700;"><?= _e('est_total') ?>:</td>
                    <td style="text-align:right;font-weight:700;" id="grandTotal">฿0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <button type="button" class="btn btn-secondary" onclick="addLine()">+ <?= _e('add_line') ?></button>
    </div>

    <!-- Attachments (supplier quotations, RFQ docs, etc.) -->
    <div class="card" style="margin-top:16px;padding:20px;">
        <h3 style="margin-top:0;"><?= _e('pr_attachments') ?></h3>
        <p style="color:#666;font-size:13px;margin-top:0;">
            <?= _e('pr_attachments_hint') ?>
        </p>
        <table class="data-table" id="attachTable">
            <thead>
                <tr>
                    <th style="width:50%;"><?= _e('file') ?></th>
                    <th><?= _e('description') ?></th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="attachBody">
                <tr data-aidx="0">
                    <td><input type="file" name="attachments[]" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx"></td>
                    <td><input type="text" name="attachment_descriptions[]" class="form-input" placeholder="例: ABC社見積書 / Supplier Quotation"></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeAttach(this)">×</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary" onclick="addAttach()">+ <?= _e('add_attachment') ?></button>
    </div>

    <div style="margin-top:16px;display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary"><?= _e('save') ?> (DRAFT)</button>
        <a href="/purchasing/requests" class="btn btn-secondary"><?= _e('cancel') ?></a>
    </div>
</form>
<script>
let aIdx = 1;
function addAttach(){
  const tb = document.getElementById('attachBody');
  const tr = document.createElement('tr');
  tr.dataset.aidx = aIdx++;
  tr.innerHTML = `
    <td><input type="file" name="attachments[]" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx"></td>
    <td><input type="text" name="attachment_descriptions[]" class="form-input" placeholder="例: ABC社見積書"></td>
    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeAttach(this)">×</button></td>`;
  tb.appendChild(tr);
}
function removeAttach(btn){ btn.closest('tr').remove(); }
</script>

<script>
let lineIdx = 0;
function addLine(){
  const tb = document.getElementById('linesBody');
  const i = lineIdx++;
  const row = document.createElement('tr');
  row.dataset.idx = i;
  row.innerHTML = `
    <td style="text-align:center;">${i+1}</td>
    <td><input type="text" name="lines[${i}][item_description]" class="form-input" required></td>
    <td><input type="number" step="0.001" min="0" name="lines[${i}][quantity]" class="form-input qty" value="1" oninput="recalc(${i})"></td>
    <td><input type="text" name="lines[${i}][unit]" class="form-input" value="PCS"></td>
    <td><input type="number" step="0.0001" min="0" name="lines[${i}][est_unit_price]" class="form-input price" value="0" oninput="recalc(${i})"></td>
    <td style="text-align:right;" class="lineTotal">฿0.00</td>
    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">×</button></td>`;
  tb.appendChild(row);
}
function removeLine(btn){
  btn.closest('tr').remove(); renumber(); recalcAll();
}
function renumber(){
  document.querySelectorAll('#linesBody tr').forEach((tr, i) => tr.children[0].textContent = i+1);
}
function recalc(i){
  const tr = document.querySelector(`#linesBody tr[data-idx="${i}"]`);
  if (!tr) return;
  const q = parseFloat(tr.querySelector('.qty').value) || 0;
  const p = parseFloat(tr.querySelector('.price').value) || 0;
  tr.querySelector('.lineTotal').textContent = '฿' + (q*p).toFixed(2);
  recalcAll();
}
function recalcAll(){
  let total = 0;
  document.querySelectorAll('#linesBody tr').forEach(tr=>{
    const q = parseFloat(tr.querySelector('.qty').value) || 0;
    const p = parseFloat(tr.querySelector('.price').value) || 0;
    total += q*p;
  });
  document.getElementById('grandTotal').textContent = '฿' + total.toFixed(2);
}
addLine(); // start with one line
</script>
