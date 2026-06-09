<?php
/**
 * PEGASUS ERP - Expense Claim Create Form
 * Variables: $claim, $categories, $isEdit
 */
extract($viewData ?? []);
$claim      = $claim ?? [];
$lineItems  = $lineItems ?? [];
$isEdit     = $isEdit ?? false;
$title      = $isEdit ? 'Edit Expense Claim' : 'New Expense Claim';
$action     = $isEdit ? '/expense/claims/' . e($claim['claim_id'] ?? '') . '/update' : '/expense/claims/store';

$categories = [
    'TRANSPORT_MILEAGE' => 'Transport - Mileage',
    'TRANSPORT_PUBLIC'  => 'Transport - Public',
    'TRANSPORT_TAXI'    => 'Transport - Taxi / Grab',
    'ACCOMMODATION'     => 'Accommodation',
    'MEAL'              => 'Meal',
    'ENTERTAINMENT'     => 'Entertainment',
    'COMMUNICATION'     => 'Communication / Phone',
    'STATIONERY'        => 'Stationery / Office Supplies',
    'POSTAGE'           => 'Postage / Courier',
    'REGISTRATION'      => 'Registration / Seminar',
    'OTHER'             => 'Other',
];
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;"><?= e($title) ?></h1>
    <a href="/expense/claims" class="btn btn-cancel">Back to List</a>
</div>

<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" id="claimForm">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <!-- Header -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">Claim Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Title <span class="required">*</span></label>
                    <input type="text" name="title" class="form-input" value="<?= e($claim['title'] ?? old('title')) ?>" required placeholder="e.g. Business trip to Rayong">
                </div>
                <div class="form-group">
                    <label class="form-label">Purpose</label>
                    <input type="text" name="purpose" class="form-input" value="<?= e($claim['purpose'] ?? old('purpose')) ?>" placeholder="Purpose of expense">
                </div>
                <div class="form-group">
                    <label class="form-label">Period</label>
                    <input type="month" name="period" class="form-input" value="<?= e($claim['period'] ?? old('period', date('Y-m'))) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h3 class="card-title">Line Items</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="addLine()">+ Add Line</button>
        </div>
        <div class="card-body" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="data-table" id="lineItemsTable">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:120px;">Date</th>
                            <th style="width:170px;">Category</th>
                            <th>Description</th>
                            <th style="width:110px;" class="text-right">Amount</th>
                            <th style="width:90px;" class="text-right">VAT</th>
                            <th style="width:80px;">Receipt</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="lineItemsBody">
                        <?php if (!empty($lineItems)): ?>
                            <?php foreach ($lineItems as $idx => $item): ?>
                                <tr class="line-row" data-index="<?= $idx ?>">
                                    <td class="text-center line-num"><?= $idx + 1 ?></td>
                                    <td><input type="date" name="lines[<?= $idx ?>][expense_date]" class="form-input" value="<?= e($item['expense_date'] ?? '') ?>" required></td>
                                    <td>
                                        <select name="lines[<?= $idx ?>][expense_category]" class="form-select line-category" onchange="onCategoryChange(this, <?= $idx ?>)">
                                            <?php foreach ($categories as $key => $label): ?>
                                                <option value="<?= e($key) ?>" <?= ($item['expense_category'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="lines[<?= $idx ?>][description]" class="form-input" value="<?= e($item['description'] ?? '') ?>" placeholder="Description"></td>
                                    <td><input type="number" name="lines[<?= $idx ?>][amount_thb]" class="form-input text-right line-amount" step="0.01" min="0" value="<?= e($item['amount_thb'] ?? '0.00') ?>" onchange="calcTotal()"></td>
                                    <td><input type="number" name="lines[<?= $idx ?>][vat_amount]" class="form-input text-right" step="0.01" min="0" value="<?= e($item['vat_amount'] ?? '0.00') ?>"></td>
                                    <td><input type="file" name="lines[<?= $idx ?>][receipt]" class="form-input" accept=".pdf,.jpg,.jpeg,.png" style="padding:4px;font-size:11px;"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)" title="Remove">&times;</button></td>
                                </tr>
                                <!-- Mileage extra fields (shown if category is TRANSPORT_MILEAGE) -->
                                <tr class="mileage-row" id="mileage_<?= $idx ?>" style="<?= ($item['expense_category'] ?? '') === 'TRANSPORT_MILEAGE' ? '' : 'display:none;' ?>">
                                    <td></td>
                                    <td colspan="7">
                                        <div style="display:flex;gap:8px;align-items:flex-end;padding:4px 0;flex-wrap:wrap;">
                                            <div style="flex:1;min-width:160px;">
                                                <label class="form-label" style="font-size:11px;">Origin</label>
                                                <input type="text" name="lines[<?= $idx ?>][origin_address]" class="form-input" value="<?= e($item['origin_address'] ?? '') ?>" placeholder="Origin address">
                                            </div>
                                            <div style="flex:1;min-width:160px;">
                                                <label class="form-label" style="font-size:11px;">Destination</label>
                                                <input type="text" name="lines[<?= $idx ?>][destination_address]" class="form-input" value="<?= e($item['destination_address'] ?? '') ?>" placeholder="Destination address">
                                            </div>
                                            <div style="width:90px;">
                                                <label class="form-label" style="font-size:11px;">Distance (km)</label>
                                                <input type="number" name="lines[<?= $idx ?>][distance_km]" class="form-input mileage-distance" step="0.1" min="0" value="<?= e($item['distance_km'] ?? '') ?>" data-index="<?= $idx ?>" onchange="calcMileage(<?= $idx ?>)">
                                            </div>
                                            <div style="width:80px;">
                                                <label class="form-label" style="font-size:11px;">Rate/km</label>
                                                <input type="number" name="lines[<?= $idx ?>][rate_per_km]" class="form-input mileage-rate" step="0.01" min="0" value="<?= e($item['rate_per_km'] ?? '5.00') ?>" data-index="<?= $idx ?>" onchange="calcMileage(<?= $idx ?>)">
                                            </div>
                                            <div style="width:100px;">
                                                <label class="form-label" style="font-size:11px;">Calculated</label>
                                                <input type="text" class="form-input mileage-calc" readonly value="<?= e($item['calculated_amount'] ?? '') ?>" style="background:#F5F5F5;">
                                            </div>
                                            <button type="button" class="btn btn-sm btn-cancel" onclick="calcDistance(<?= $idx ?>)" title="Calculate distance via API">Calculate Distance</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right" style="font-weight:600;">Total</td>
                            <td class="text-right" style="font-weight:600;" id="grandTotal"><?= formatMoney(0) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:40px;">
        <a href="/expense/claims" class="btn btn-cancel">Cancel</a>
        <button type="submit" name="action" value="draft" class="btn btn-cancel">Save Draft</button>
        <button type="submit" name="action" value="submit" class="btn btn-primary">Submit</button>
    </div>
</form>

<script>
var lineIndex = <?= max(count($lineItems), 0) ?>;

var categories = <?= json_encode($categories) ?>;

function addLine() {
    var idx = lineIndex++;
    var catOptions = '';
    for (var key in categories) {
        catOptions += '<option value="' + key + '">' + categories[key] + '</option>';
    }

    var row = '<tr class="line-row" data-index="' + idx + '">'
        + '<td class="text-center line-num">' + (idx + 1) + '</td>'
        + '<td><input type="date" name="lines[' + idx + '][expense_date]" class="form-input" required></td>'
        + '<td><select name="lines[' + idx + '][expense_category]" class="form-select line-category" onchange="onCategoryChange(this,' + idx + ')">' + catOptions + '</select></td>'
        + '<td><input type="text" name="lines[' + idx + '][description]" class="form-input" placeholder="Description"></td>'
        + '<td><input type="number" name="lines[' + idx + '][amount_thb]" class="form-input text-right line-amount" step="0.01" min="0" value="0.00" onchange="calcTotal()"></td>'
        + '<td><input type="number" name="lines[' + idx + '][vat_amount]" class="form-input text-right" step="0.01" min="0" value="0.00"></td>'
        + '<td><input type="file" name="lines[' + idx + '][receipt]" class="form-input" accept=".pdf,.jpg,.jpeg,.png" style="padding:4px;font-size:11px;"></td>'
        + '<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)" title="Remove">&times;</button></td>'
        + '</tr>';

    var mileageRow = '<tr class="mileage-row" id="mileage_' + idx + '" style="display:none;">'
        + '<td></td>'
        + '<td colspan="7">'
        + '<div style="display:flex;gap:8px;align-items:flex-end;padding:4px 0;flex-wrap:wrap;">'
        + '<div style="flex:1;min-width:160px;"><label class="form-label" style="font-size:11px;">Origin</label><input type="text" name="lines[' + idx + '][origin_address]" class="form-input" placeholder="Origin address"></div>'
        + '<div style="flex:1;min-width:160px;"><label class="form-label" style="font-size:11px;">Destination</label><input type="text" name="lines[' + idx + '][destination_address]" class="form-input" placeholder="Destination address"></div>'
        + '<div style="width:90px;"><label class="form-label" style="font-size:11px;">Distance (km)</label><input type="number" name="lines[' + idx + '][distance_km]" class="form-input mileage-distance" step="0.1" min="0" data-index="' + idx + '" onchange="calcMileage(' + idx + ')"></div>'
        + '<div style="width:80px;"><label class="form-label" style="font-size:11px;">Rate/km</label><input type="number" name="lines[' + idx + '][rate_per_km]" class="form-input mileage-rate" step="0.01" min="0" value="5.00" data-index="' + idx + '" onchange="calcMileage(' + idx + ')"></div>'
        + '<div style="width:100px;"><label class="form-label" style="font-size:11px;">Calculated</label><input type="text" class="form-input mileage-calc" readonly style="background:#F5F5F5;"></div>'
        + '<button type="button" class="btn btn-sm btn-cancel" onclick="calcDistance(' + idx + ')">Calculate Distance</button>'
        + '</div>'
        + '</td>'
        + '</tr>';

    document.getElementById('lineItemsBody').insertAdjacentHTML('beforeend', row + mileageRow);
    renumberLines();
}

function removeLine(btn) {
    var lineRow = btn.closest('.line-row');
    var idx = lineRow.dataset.index;
    var mileageRow = document.getElementById('mileage_' + idx);
    if (mileageRow) mileageRow.remove();
    lineRow.remove();
    renumberLines();
    calcTotal();
}

function renumberLines() {
    var rows = document.querySelectorAll('#lineItemsBody .line-row');
    rows.forEach(function(row, i) {
        var numCell = row.querySelector('.line-num');
        if (numCell) numCell.textContent = i + 1;
    });
}

function onCategoryChange(select, idx) {
    var mileageRow = document.getElementById('mileage_' + idx);
    if (mileageRow) {
        mileageRow.style.display = (select.value === 'TRANSPORT_MILEAGE') ? '' : 'none';
    }
}

function calcTotal() {
    var amounts = document.querySelectorAll('.line-amount');
    var total = 0;
    amounts.forEach(function(el) { total += parseFloat(el.value) || 0; });
    document.getElementById('grandTotal').textContent = '\u0E3F' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function calcMileage(idx) {
    var row = document.getElementById('mileage_' + idx);
    if (!row) return;
    var dist = parseFloat(row.querySelector('.mileage-distance').value) || 0;
    var rate = parseFloat(row.querySelector('.mileage-rate').value) || 5.00;
    var calc = dist * rate;
    row.querySelector('.mileage-calc').value = calc.toFixed(2);

    // Update the amount field in the corresponding line row
    var lineRow = document.querySelector('.line-row[data-index="' + idx + '"]');
    if (lineRow) {
        var amountInput = lineRow.querySelector('.line-amount');
        if (amountInput) {
            amountInput.value = calc.toFixed(2);
            calcTotal();
        }
    }
}

function calcDistance(idx) {
    var row = document.getElementById('mileage_' + idx);
    if (!row) return;
    var origin = row.querySelector('[name$="[origin_address]"]').value;
    var dest   = row.querySelector('[name$="[destination_address]"]').value;
    if (!origin || !dest) {
        alert('Please enter both origin and destination addresses.');
        return;
    }

    fetch('/api/distance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ origin: origin, destination: dest })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.distance_km) {
            row.querySelector('.mileage-distance').value = data.distance_km;
            calcMileage(idx);
        } else {
            alert(data.message || 'Could not calculate distance.');
        }
    })
    .catch(function() { alert('Network error.'); });
}

// Initialize total on page load
document.addEventListener('DOMContentLoaded', function() { calcTotal(); });
</script>
