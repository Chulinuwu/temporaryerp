<?php
/**
 * PEGASUS ERP - Journal Entry Form
 * Variables: $journalEntry (null for new), $accounts, $periods, $errors
 */
$isEdit = !empty($journalEntry);
$pageTitle = ($isEdit ? "Edit JE #{$journalEntry['je_no']}" : 'New Journal Entry') . ' - PEGASUS ERP';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? _e('edit') . ' ' . _e('journal_entry') . ' #' . htmlspecialchars($journalEntry['je_no']) : _e('new_journal') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/accounting/journal"><?= _e('accounting') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/accounting/journal"><?= _e('journal_entry') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= $isEdit ? _e('edit') : _e('create') ?></span>
        </div>
    </div>
    <a href="/accounting/journal" class="btn btn-cancel"><?= _e('back') ?></a>
</div>

<form method="POST" action="<?= $isEdit ? '/accounting/journal/' . htmlspecialchars($journalEntry['je_id']) : '/accounting/journal' ?>" id="jeForm">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <!-- Header Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= _e('journal_entry') ?> <?= _e('details') ?></h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Date <span class="required">*</span></label>
                    <input type="date" name="je_date" class="form-input" value="<?= htmlspecialchars($journalEntry['je_date'] ?? date('Y-m-d')) ?>" required onchange="updatePeriod(this.value)">
                </div>
                <div class="form-group">
                    <label class="form-label">Period</label>
                    <input type="text" id="periodDisplay" class="form-input" value="<?= htmlspecialchars($journalEntry['period'] ?? date('Y-m')) ?>" readonly style="background:#F5F5F5;">
                    <input type="hidden" name="period" id="period" value="<?= htmlspecialchars($journalEntry['period'] ?? date('Y-m')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Reference Type</label>
                    <select name="reference_type" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach (['INVOICE','PAYMENT','ADJUSTMENT','OPENING','OTHER'] as $rt): ?>
                            <option value="<?= $rt ?>" <?= ($journalEntry['reference_type'] ?? '') === $rt ? 'selected' : '' ?>><?= $rt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-full">
                    <label class="form-label">Description <span class="required">*</span></label>
                    <input type="text" name="description" class="form-input" value="<?= htmlspecialchars($journalEntry['description'] ?? '') ?>" required placeholder="Journal entry description">
                </div>
                <div class="form-group">
                    <label class="form-label">Reference ID</label>
                    <input type="text" name="reference_id" class="form-input" value="<?= htmlspecialchars($journalEntry['reference_id'] ?? '') ?>" placeholder="e.g. INV-0001">
                </div>
            </div>
        </div>
    </div>

    <!-- Journal Lines -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Journal Lines</h3>
            <button type="button" class="btn btn-primary btn-sm" onclick="addJELine()">+ Add Line</button>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper" style="box-shadow:none;">
                <table class="data-table" id="jeLinesTable">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:200px;"><?= _e('account_code') ?></th>
                            <th><?= _e('description') ?></th>
                            <th style="width:150px;" class="text-right"><?= _e('debit') ?></th>
                            <th style="width:150px;" class="text-right"><?= _e('credit') ?></th>
                            <th style="width:50px;" class="text-center"><?= _e('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody id="jeLinesBody">
                        <?php if (!empty($journalEntry['lines'])): ?>
                            <?php foreach ($journalEntry['lines'] as $i => $line): ?>
                                <tr class="je-line" data-line="<?= $i ?>">
                                    <td class="text-center je-line-no"><?= $i + 1 ?></td>
                                    <td>
                                        <select name="lines[<?= $i ?>][account_code]" class="form-select je-account" required>
                                            <option value="">-- Select Account --</option>
                                            <?php foreach ($accounts ?? [] as $acc): ?>
                                                <option value="<?= htmlspecialchars($acc['account_code']) ?>" <?= ($line['account_code'] ?? '') == $acc['account_code'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="lines[<?= $i ?>][description]" class="form-input" value="<?= htmlspecialchars($line['description'] ?? '') ?>"></td>
                                    <td><input type="number" name="lines[<?= $i ?>][debit_amount]" class="form-input text-right je-debit" value="<?= htmlspecialchars($line['debit_amount'] ?? 0) ?>" step="0.01" min="0" onchange="jeCalcLine(this, 'debit')"></td>
                                    <td><input type="number" name="lines[<?= $i ?>][credit_amount]" class="form-input text-right je-credit" value="<?= htmlspecialchars($line['credit_amount'] ?? 0) ?>" step="0.01" min="0" onchange="jeCalcLine(this, 'credit')"></td>
                                    <td class="text-center actions"><button type="button" class="btn btn-danger btn-sm" onclick="removeJELine(this)">&times;</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#F5F5F5;font-weight:600;">
                            <td colspan="3" class="text-right">Totals</td>
                            <td class="text-right" id="totalDebit">0.00</td>
                            <td class="text-right" id="totalCredit">0.00</td>
                            <td></td>
                        </tr>
                        <tr id="balanceRow">
                            <td colspan="3" class="text-right"><strong>Difference</strong></td>
                            <td colspan="2" class="text-center" id="balanceDiff" style="font-weight:700;">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card" style="display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;">
        <a href="/accounting/journal" class="btn btn-cancel"><?= _e('cancel') ?></a>
        <button type="submit" name="action" value="save" class="btn btn-cancel"><?= _e('save') ?></button>
        <button type="submit" name="action" value="post" class="btn btn-primary" id="postBtn"><?= _e('submit') ?></button>
    </div>
</form>

<script>
let jeLineIndex = <?= isset($journalEntry['lines']) ? count($journalEntry['lines']) : 0 ?>;

const accountOptions = `<option value="">-- Select Account --</option>` +
    <?php
    $opts = '';
    foreach ($accounts ?? [] as $acc) {
        $val = htmlspecialchars($acc['account_code']);
        $label = htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']);
        $opts .= "<option value=\"{$val}\">{$label}</option>";
    }
    echo json_encode($opts);
    ?>;

function addJELine() {
    const tbody = document.getElementById('jeLinesBody');
    const i = jeLineIndex++;
    const tr = document.createElement('tr');
    tr.className = 'je-line';
    tr.dataset.line = i;
    tr.innerHTML = `
        <td class="text-center je-line-no">${tbody.querySelectorAll('.je-line').length + 1}</td>
        <td><select name="lines[${i}][account_code]" class="form-select je-account" required>${accountOptions}</select></td>
        <td><input type="text" name="lines[${i}][description]" class="form-input"></td>
        <td><input type="number" name="lines[${i}][debit_amount]" class="form-input text-right je-debit" value="0" step="0.01" min="0" onchange="jeCalcLine(this, 'debit')"></td>
        <td><input type="number" name="lines[${i}][credit_amount]" class="form-input text-right je-credit" value="0" step="0.01" min="0" onchange="jeCalcLine(this, 'credit')"></td>
        <td class="text-center actions"><button type="button" class="btn btn-danger btn-sm" onclick="removeJELine(this)">&times;</button></td>
    `;
    tbody.appendChild(tr);
}

function removeJELine(btn) {
    btn.closest('tr').remove();
    renumberJELines();
    calcJETotals();
}

function renumberJELines() {
    document.querySelectorAll('#jeLinesBody .je-line').forEach((row, idx) => {
        row.querySelector('.je-line-no').textContent = idx + 1;
    });
}

function jeCalcLine(el, type) {
    const row = el.closest('tr');
    if (type === 'debit' && parseFloat(el.value) > 0) {
        row.querySelector('.je-credit').value = 0;
    } else if (type === 'credit' && parseFloat(el.value) > 0) {
        row.querySelector('.je-debit').value = 0;
    }
    calcJETotals();
}

function calcJETotals() {
    let totalDebit = 0, totalCredit = 0;
    document.querySelectorAll('#jeLinesBody .je-line').forEach(row => {
        totalDebit += parseFloat(row.querySelector('.je-debit')?.value) || 0;
        totalCredit += parseFloat(row.querySelector('.je-credit')?.value) || 0;
    });
    const diff = totalDebit - totalCredit;
    document.getElementById('totalDebit').textContent = totalDebit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('totalCredit').textContent = totalCredit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const diffEl = document.getElementById('balanceDiff');
    diffEl.textContent = Math.abs(diff).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (Math.abs(diff) < 0.005) {
        diffEl.style.color = 'var(--color-success)';
        diffEl.textContent = 'Balanced';
        document.getElementById('postBtn').disabled = false;
    } else {
        diffEl.style.color = 'var(--color-danger)';
        diffEl.textContent = (diff > 0 ? 'Debit excess: ' : 'Credit excess: ') + Math.abs(diff).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('postBtn').disabled = true;
    }
}

function updatePeriod(dateStr) {
    if (dateStr) {
        const period = dateStr.substring(0, 7);
        document.getElementById('period').value = period;
        document.getElementById('periodDisplay').value = period;
    }
}

document.addEventListener('DOMContentLoaded', calcJETotals);
</script>
