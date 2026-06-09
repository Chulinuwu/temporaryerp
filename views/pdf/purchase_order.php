<?php require __DIR__ . '/_layout.php'; ?>
<style>
.po-page { padding: 0; font-size: 10px; }
.po-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.po-logo-area { display: flex; align-items: flex-start; gap: 10px; }
.po-logo { width: 44px; height: 44px; background: #003366; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; flex-shrink: 0; }
.po-company-name { font-size: 15px; font-weight: 700; color: #003366; }
.po-company-info { font-size: 8.5px; color: #555; line-height: 1.5; }
.po-title-area { text-align: right; }
.po-doc-title { font-size: 26px; font-weight: 700; color: #003366; letter-spacing: 1px; }
.po-doc-title-th { font-size: 12px; color: #003366; margin-bottom: 4px; }
.po-info-section { border: 1.5px solid #003366; margin-bottom: 14px; display: flex; }
.po-info-left { flex: 1; padding: 10px 14px; border-right: 1.5px solid #003366; }
.po-info-right { flex: 1; padding: 10px 14px; }
.po-info-title { font-size: 10px; font-weight: 700; color: #003366; text-decoration: underline; margin-bottom: 6px; }
.po-info-row { display: flex; margin-bottom: 2px; font-size: 10px; }
.po-info-label { width: 100px; font-weight: 600; color: #333; flex-shrink: 0; }
.po-info-val { flex: 1; }
.po-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.po-table thead th { background: #003366; color: #fff; padding: 6px 8px; font-size: 9.5px; font-weight: 600; text-align: left; }
.po-table thead th.r { text-align: right; }
.po-table tbody td { border-bottom: 0.5px solid #ddd; padding: 5px 8px; font-size: 10px; }
.po-table tbody td.r { text-align: right; }
.po-totals { display: flex; justify-content: flex-end; margin-top: 15px; margin-bottom: 12px; }
.po-totals-table { width: 340px; border-collapse: collapse; }
.po-totals-table td { padding: 3px 10px; font-size: 10px; }
.po-totals-table td:last-child { text-align: right; font-weight: 600; width: 120px; }
.po-grand-total td { background: #003366; color: #fff; font-size: 11px; font-weight: 700; padding: 6px 10px; }
.po-signatures { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 10px; border-top: 1px solid #003366; }
.po-sig-box { text-align: center; width: 220px; }
.po-sig-name { font-size: 10px; font-weight: 700; text-decoration: underline; margin-top: 40px; }
.po-sig-role { font-size: 9px; color: #555; margin-top: 2px; }
</style>

<?php $h = $header; ?>
<div class="page po-page">

    <div class="po-header">
        <div class="po-logo-area" style="flex-direction:column;align-items:flex-start;gap:6px;">
            <?php $logoPath = __DIR__ . '/../../public/assets/tomas_tech_logo_R1.png'; ?>
            <?php if (file_exists($logoPath)): ?>
                <img src="/assets/tomas_tech_logo_R1.png" alt="TOMAS TECH" style="height:54px;width:auto;">
            <?php else: ?>
                <div class="po-logo">T</div>
            <?php endif; ?>
            <div class="po-company-info">
                No.1 M/D tower 1/61, Unit C1, Soi Bangna-trad 25, Debaratna Rd.,<br>
                Bangkok 10260 Thailand | Tel: +66-98-271-9141 | Tax ID: 0-1155-64003-36-4
            </div>
        </div>
        <div class="po-title-area">
            <div class="po-doc-title">PURCHASE ORDER</div>
            <div class="po-doc-title-th">ใบสั่งซื้อ</div>
        </div>
    </div>

    <div class="po-info-section">
        <div class="po-info-left">
            <div class="po-info-title">SUPPLIER INFORMATION</div>
            <div class="po-info-row"><div class="po-info-label">Company :</div><div class="po-info-val"><strong><?= e($h['supplier_name'] ?? '') ?></strong></div></div>
            <div class="po-info-row"><div class="po-info-label">Tax ID :</div><div class="po-info-val"><?= e($h['supplier_tax_id'] ?? '') ?></div></div>
            <div class="po-info-row"><div class="po-info-label">Address :</div><div class="po-info-val"><?= e($h['supplier_address'] ?? '') ?></div></div>
            <div class="po-info-row"><div class="po-info-label">Contact :</div><div class="po-info-val"><?= e($h['contact_person'] ?? '') ?></div></div>
            <div class="po-info-row"><div class="po-info-label">Tel :</div><div class="po-info-val"><?= e($h['supplier_phone'] ?? '') ?></div></div>
        </div>
        <div class="po-info-right">
            <div class="po-info-title">DOCUMENT DETAILS</div>
            <div class="po-info-row"><div class="po-info-label">PO No. :</div><div class="po-info-val"><strong><?= e($h['po_no'] ?? '') ?></strong></div></div>
            <div class="po-info-row"><div class="po-info-label">Date :</div><div class="po-info-val"><?= e($h['order_date'] ?? '') ?></div></div>
            <div class="po-info-row"><div class="po-info-label">Delivery :</div><div class="po-info-val"><?= e($h['delivery_date'] ?? '-') ?></div></div>
            <div class="po-info-row"><div class="po-info-label">Currency :</div><div class="po-info-val"><?= e($h['currency_code'] ?? 'THB') ?></div></div>
            <div class="po-info-row"><div class="po-info-label">Status :</div><div class="po-info-val"><?= e($h['status'] ?? 'DRAFT') ?></div></div>
        </div>
    </div>

    <table class="po-table">
        <thead>
            <tr>
                <th style="width:35px;">No.</th>
                <th>Description</th>
                <th style="width:60px;" class="r">Qty</th>
                <th style="width:50px;">Unit</th>
                <th style="width:110px;" class="r">Unit Price (<?= e($h['currency_code'] ?? 'THB') ?>)</th>
                <th style="width:110px;" class="r">Amount (<?= e($h['currency_code'] ?? 'THB') ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $i => $line):
                $qty = floatval($line['quantity'] ?? 0);
                $unitPrice = floatval($line['unit_price'] ?? 0);
                $extPrice = floatval($line['ext_price'] ?? $qty * $unitPrice);
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($line['item_description'] ?? '') ?></td>
                <td class="r"><?= number_format($qty, 2) ?></td>
                <td><?= e($line['unit'] ?? 'EA') ?></td>
                <td class="r"><?= number_format($unitPrice, 2) ?></td>
                <td class="r"><?= number_format($extPrice, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($lines)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:#999;">No items</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    $subtotal = floatval($h['subtotal_thb'] ?? 0);
    $vatRate = floatval($h['vat_rate'] ?? 7);
    $vatAmount = floatval($h['vat_amount'] ?? 0);
    $whtAmount = floatval($h['wht_amount'] ?? 0);
    $paymentAmount = floatval($h['payment_amount'] ?? 0);
    ?>
    <div class="po-totals">
        <table class="po-totals-table">
            <tr><td>Subtotal</td><td><?= number_format($subtotal, 2) ?></td></tr>
            <tr><td>VAT <?= number_format($vatRate, 0) ?>%</td><td><?= number_format($vatAmount, 2) ?></td></tr>
            <?php if ($whtAmount > 0): ?>
            <tr><td>WHT</td><td>-<?= number_format($whtAmount, 2) ?></td></tr>
            <?php endif; ?>
            <tr class="po-grand-total"><td>PAYMENT AMOUNT</td><td><?= number_format($paymentAmount, 2) ?></td></tr>
        </table>
    </div>

    <?php
    $isApproved = ($h['status'] ?? '') === 'APPROVED';
    $stampPath = __DIR__ . '/../../public/assets/approver_sign.png';
    $hasStamp = $isApproved && file_exists($stampPath);
    ?>
    <div class="po-signatures">
        <div class="po-sig-box" style="position:relative;">
            <?php if ($hasStamp): ?>
                <img src="/assets/approver_sign.png" alt="Approved"
                     style="position:absolute; top:-15px; left:50%; transform:translateX(-50%); height:70px; mix-blend-mode:multiply;">
            <?php endif; ?>
            <div class="po-sig-name">TOMAS TECH CO.,LTD.</div>
            <div class="po-sig-role">Authorized Signature / ผู้สั่งซื้อ</div>
            <?php if ($isApproved && !empty($h['approval_date'])): ?>
                <div style="font-size:10px;color:#555;margin-top:3px;">Approved: <?= e($h['approval_date']) ?></div>
            <?php endif; ?>
        </div>
        <div class="po-sig-box">
            <div class="po-sig-name"><?= e($h['supplier_name'] ?? '') ?></div>
            <div class="po-sig-role">Accepted & Confirmed / ยอมรับและยืนยัน</div>
        </div>
    </div>

</div>

</div><!-- /preview-paper -->
</div><!-- /preview-wrapper -->
</body></html>
