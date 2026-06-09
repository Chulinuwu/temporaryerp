<?php require __DIR__ . '/_layout.php'; ?>
<style>
/* ── Quotation PDF Styles ── */
.q-page { padding: 0; font-size: 10px; }

/* Header */
.q-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.q-logo-area { display: flex; align-items: flex-start; gap: 10px; }
.q-logo { width: 44px; height: 44px; background: #003366; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; flex-shrink: 0; }
.q-company-name { font-size: 15px; font-weight: 700; color: #003366; margin-bottom: 1px; }
.q-company-name-th { font-size: 10px; color: #003366; margin-bottom: 3px; }
.q-company-info { font-size: 8.5px; color: #555; line-height: 1.5; }
.q-title-area { text-align: right; }
.q-doc-title { font-size: 26px; font-weight: 700; color: #003366; letter-spacing: 1px; }
.q-doc-title-th { font-size: 12px; color: #003366; margin-bottom: 4px; }
.q-doc-meta { font-size: 9px; color: #666; text-align: right; }

/* Info Section */
.q-info-section { border: 1.5px solid #003366; margin-bottom: 14px; display: flex; }
.q-info-left { flex: 1; padding: 10px 14px; border-right: 1.5px solid #003366; }
.q-info-right { flex: 1; padding: 10px 14px; }
.q-info-title { font-size: 10px; font-weight: 700; color: #003366; text-decoration: underline; margin-bottom: 6px; }
.q-info-row { display: flex; margin-bottom: 2px; font-size: 10px; }
.q-info-label { width: 90px; font-weight: 600; color: #333; flex-shrink: 0; }
.q-info-label-r { width: 100px; font-weight: 600; color: #333; flex-shrink: 0; }
.q-info-val { flex: 1; }
.q-info-val strong { font-weight: 700; }

/* Line Items Table */
.q-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.q-table thead th { background: #003366; color: #fff; padding: 6px 8px; font-size: 9.5px; font-weight: 600; text-align: left; border: none; }
.q-table thead th.r { text-align: right; }
.q-table thead th.c { text-align: center; }
.q-table tbody td { border-bottom: 0.5px solid #ddd; padding: 5px 8px; font-size: 10px; vertical-align: top; }
.q-table tbody td.r { text-align: right; }
.q-table tbody td.c { text-align: center; }
.q-table tbody tr:first-child td { border-top: none; }
.q-table tbody tr:nth-child(even) td { background: #f4f6f8; }
/* Ensure minimum rows space */
.q-table-wrapper { min-height: 200px; border-left: 0.5px solid #ddd; border-right: 0.5px solid #ddd; border-bottom: 0.5px solid #ddd; }

/* Totals */
.q-totals { display: flex; justify-content: flex-end; margin-top: 15px; margin-bottom: 12px; }
.q-totals-table { width: 340px; border-collapse: collapse; }
.q-totals-table td { padding: 3px 10px; font-size: 10px; }
.q-totals-table td:last-child { text-align: right; font-weight: 600; width: 120px; }
.q-totals-table td:first-child { color: #333; }
.q-totals-table .q-grand-total td { background: #003366; color: #fff; font-size: 11px; font-weight: 700; padding: 6px 10px; }

/* Payment Terms */
.q-payment { margin-bottom: 12px; background: #f4f6f8; border: 1px solid #d0d8e0; padding: 12px 16px; }
.q-payment-title { font-size: 10px; font-weight: 700; color: #003366; text-decoration: underline; margin-bottom: 4px; }
.q-payment-text { font-size: 9.5px; margin-bottom: 8px; }
.q-bank-grid { display: flex; flex-wrap: wrap; font-size: 9.5px; }
.q-bank-row { display: flex; width: 50%; margin-bottom: 2px; }
.q-bank-label { font-weight: 700; color: #c00; width: 100px; flex-shrink: 0; }
.q-bank-val { flex: 1; }

/* Signatures */
.q-signatures { display: flex; justify-content: space-between; margin-top: 36px; padding-top: 0; }
.q-sig-box { text-align: center; width: 240px; }
.q-sig-line { border-bottom: 1.2px solid #000; margin-top: 50px; margin-bottom: 4px; width: 100%; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.q-sig-name { font-size: 10px; font-weight: 700; margin-top: 4px; }
.q-sig-company { font-size: 9px; color: #555; }
.q-sig-role { font-size: 9px; color: #555; margin-top: 2px; }
.q-sig-date { font-size: 9px; color: #555; margin-top: 6px; display: block; text-align: left; min-width: 160px; padding-bottom: 2px; }

/* Category rows */
.q-cat-row td { font-weight: 700; background: #f0f4f8 !important; border-bottom: 1px solid #ccc !important; }
</style>

<?php
$h = $header;
$preparedBy = $h['prepared_by_name'] ?? 'Admin';
$docTemplate = $h['document_template'] ?? 'SYS MN Rev#01';
$docTemplate = str_replace('-', ' ', str_replace('Rev#', 'Rev#', $docTemplate));
$validUntil = $h['expiry_date'] ?? '';
$pricing = 'VAT Excluded';
if (floatval($h['vat_rate'] ?? 0) > 0) {
    $pricing = 'VAT Excluded';
}
?>

<div class="page q-page">

    <!-- ===== HEADER ===== -->
    <div class="q-header">
        <div class="q-logo-area" style="flex-direction:column;align-items:flex-start;gap:6px;">
            <?php $logoPath = __DIR__ . '/../../public/assets/tomas_tech_logo_R1.png'; ?>
            <?php if (file_exists($logoPath)): ?>
                <img src="/assets/tomas_tech_logo_R1.png" alt="TOMAS TECH" style="height:54px;width:auto;">
            <?php else: ?>
                <div class="q-logo">T</div>
            <?php endif; ?>
            <div class="q-company-info">
                No.1 M/D tower 1/61, Unit C1, Soi Bangna-trad 25, Debaratna Rd.,<br>
                Khawaeng Bang Na Nuea, Khet Bang Na, Bangkok 10260 Thailand<br>
                Tel: +66-98-271-9141 | E-mail: info@tomastc.com | Tax ID: 0-1155-64003-36-4
            </div>
        </div>
        <div class="q-title-area">
            <div class="q-doc-title">QUOTATION</div>
            <div class="q-doc-title-th">ใบเสนอราคา</div>
            <div class="q-doc-meta">
                <?= e($docTemplate) ?><br>
                Page 1 of <?= $totalPages ?? 1 ?>
            </div>
        </div>
    </div>

    <!-- ===== CUSTOMER INFO / DOCUMENT DETAILS ===== -->
    <div class="q-info-section">
        <div class="q-info-left">
            <div class="q-info-title">CUSTOMER INFORMATION</div>
            <div class="q-info-row">
                <div class="q-info-label">Company :</div>
                <div class="q-info-val"><strong><?= e($h['customer_name'] ?? '') ?></strong></div>
            </div>
            <?php if (!empty($h['customer_name_local'])): ?>
            <div class="q-info-row">
                <div class="q-info-label"></div>
                <div class="q-info-val"><?= e($h['customer_name_local']) ?></div>
            </div>
            <?php endif; ?>
            <div class="q-info-row">
                <div class="q-info-label">Tax ID :</div>
                <div class="q-info-val"><?= e($h['customer_tax_id'] ?? '') ?></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label">Address :</div>
                <div class="q-info-val"><?= e($h['customer_address'] ?? '') ?></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label">Tel :</div>
                <div class="q-info-val"><?= e($h['customer_phone'] ?? '') ?></div>
            </div>
            <?php
            $attnName  = $h['attention_name']  ?? ($h['contact_person'] ?? '');
            $attnEmail = $h['attention_email'] ?? ($h['customer_email'] ?? '');
            $attnPhone = $h['attention_phone'] ?? '';
            ?>
            <?php if (!empty($attnName)): ?>
            <div class="q-info-row">
                <div class="q-info-label">Attention :</div>
                <div class="q-info-val"><strong><?= e($attnName) ?></strong></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($attnEmail)): ?>
            <div class="q-info-row">
                <div class="q-info-label">E-mail :</div>
                <div class="q-info-val"><?= e($attnEmail) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($attnPhone)): ?>
            <div class="q-info-row">
                <div class="q-info-label">Contact Tel :</div>
                <div class="q-info-val"><?= e($attnPhone) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="q-info-right">
            <div class="q-info-title">DOCUMENT DETAILS</div>
            <div class="q-info-row">
                <div class="q-info-label-r">Doc No. :</div>
                <div class="q-info-val"><strong><?= e($h['quotation_no'] ?? '') ?></strong></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label-r">Date :</div>
                <div class="q-info-val"><?= e($h['issue_date'] ?? '') ?></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label-r">Valid Until :</div>
                <div class="q-info-val"><?= e($validUntil ?: '-') ?></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label-r">Pricing :</div>
                <div class="q-info-val"><?= $pricing ?></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label-r">Currency :</div>
                <div class="q-info-val"><?= e($h['currency_code'] ?? 'THB') ?></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label-r">Prepared by :</div>
                <div class="q-info-val"><?= e($preparedBy) ?></div>
            </div>
            <?php if (!empty($h['in_charge_name'])): ?>
            <div class="q-info-row">
                <div class="q-info-label-r">In Charge :</div>
                <div class="q-info-val"><?= e($h['in_charge_name']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($h['project_name'])): ?>
            <div class="q-info-row">
                <div class="q-info-label-r">Project :</div>
                <div class="q-info-val"><?= e($h['project_name']) ?><?= !empty($h['project_code']) ? ' (' . e($h['project_code']) . ')' : '' ?></div>
            </div>
            <?php endif; ?>
            <div class="q-info-row">
                <div class="q-info-label-r">Status :</div>
                <div class="q-info-val"><?= e($h['status'] ?? 'DRAFT') ?></div>
            </div>
        </div>
    </div>

    <!-- ===== LINE ITEMS TABLE ===== -->
    <div class="q-table-wrapper">
        <table class="q-table">
            <thead>
                <tr>
                    <th style="width:35px;" class="c">No.</th>
                    <th>Description</th>
                    <th style="width:60px;" class="r">Qty</th>
                    <th style="width:50px;" class="c">Unit</th>
                    <th style="width:110px;" class="r">Unit Price (<?= e($h['currency_code'] ?? 'THB') ?>)</th>
                    <th style="width:110px;" class="r">Amount (<?= e($h['currency_code'] ?? 'THB') ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($lines as $line):
                    $isCat = !empty($line['is_category_row']);
                    $lineNoDisplay = $line['line_no'] ?? '';
                    if ($isCat):
                ?>
                    <tr class="q-cat-row">
                        <td class="c"><?= e($lineNoDisplay) ?></td>
                        <td colspan="5" style="padding-left:<?= (substr_count($lineNoDisplay, '-') * 15 + 8) ?>px;">
                            <?= e($line['item_description'] ?? '') ?>
                        </td>
                    </tr>
                <?php else:
                    $qty = floatval($line['quantity'] ?? 0);
                    $unitPrice = floatval($line['unit_price'] ?? 0);
                    $extPrice = floatval($line['ext_price'] ?? 0);
                    if ($extPrice == 0 && $qty > 0 && $unitPrice > 0) {
                        $extPrice = $qty * $unitPrice;
                    }
                ?>
                    <tr>
                        <td class="c"><?= e($lineNoDisplay) ?></td>
                        <td><?= e($line['item_description'] ?? '') ?></td>
                        <td class="r"><?= number_format($qty, 2) ?></td>
                        <td class="c"><?= e($line['unit'] ?? 'EA') ?></td>
                        <td class="r"><?= number_format($unitPrice, 2) ?></td>
                        <td class="r"><?= number_format($extPrice, 2) ?></td>
                    </tr>
                <?php endif; endforeach; ?>
                <?php if (empty($lines)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#999;">No items</td></tr>
                <?php endif; ?>
                <?php
                // Add empty rows to fill space (minimum ~8 rows visible)
                $emptyRows = max(0, 8 - count($lines));
                for ($i = 0; $i < $emptyRows; $i++):
                ?>
                    <tr><td colspan="6" style="height:20px;border-bottom:0.5px solid #eee;">&nbsp;</td></tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <!-- ===== TOTALS ===== -->
    <?php
    $subtotal = floatval($h['subtotal_thb'] ?? 0);
    $discountAmt = floatval($h['discount_amount'] ?? 0);
    $netAmount = $subtotal - $discountAmt;
    $vatRate = floatval($h['vat_rate'] ?? 7);
    $vatAmount = floatval($h['vat_amount'] ?? 0);
    $grandTotal = floatval($h['grand_total_thb'] ?? 0);
    ?>
    <div class="q-totals">
        <table class="q-totals-table">
            <tr>
                <td>Subtotal / ยอดรวมก่อนภาษี</td>
                <td><?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php if ($discountAmt > 0): ?>
            <tr>
                <td>Discount / ส่วนลด</td>
                <td>-<?= number_format($discountAmt, 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Net Amount / ยอดสุทธิ</td>
                <td><?= number_format($netAmount, 2) ?></td>
            </tr>
            <tr>
                <td>VAT <?= number_format($vatRate, 0) ?>% / ภาษีมูลค่าเพิ่ม</td>
                <td><?= number_format($vatAmount, 2) ?></td>
            </tr>
            <tr class="q-grand-total">
                <td>GRAND TOTAL / ยอดรวมทั้งสิ้น</td>
                <td><?= number_format($grandTotal, 2) ?></td>
            </tr>
        </table>
    </div>

    <!-- ===== PAYMENT TERMS ===== -->
    <div class="q-payment">
        <div class="q-payment-title">Payment Terms / เงื่อนไขการชำระเงิน</div>
        <div class="q-payment-text">
            <?php if (!empty($paymentInstallments)): ?>
                <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:3px 6px;border-bottom:1px solid #003366;width:30px;">#</th>
                            <th style="text-align:right;padding:3px 6px;border-bottom:1px solid #003366;width:60px;">%</th>
                            <th style="text-align:left;padding:3px 6px;border-bottom:1px solid #003366;">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentInstallments as $inst): ?>
                            <tr>
                                <td style="padding:3px 6px;"><?= e($inst['seq_no']) ?></td>
                                <td style="padding:3px 6px;text-align:right;font-weight:600;"><?= number_format(floatval($inst['percentage']), 2) ?>%</td>
                                <td style="padding:3px 6px;"><?= e($inst['description_en'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (!empty($h['remark_text'])): ?>
                <?= nl2br(e($h['remark_text'])) ?>
            <?php elseif (!empty($h['payment_term_name'])): ?>
                <?= e($h['payment_term_name']) ?>
            <?php else: ?>
                50% Deposit upon PO confirmation, Balance upon delivery
            <?php endif; ?>
        </div>
        <div class="q-payment-title">Bank Details / ข้อมูลธนาคาร</div>
        <div class="q-bank-grid">
            <div class="q-bank-row">
                <div class="q-bank-label">Bank Name :</div>
                <div class="q-bank-val">Bangkok Bank</div>
            </div>
            <div class="q-bank-row">
                <div class="q-bank-label">Branch :</div>
                <div class="q-bank-val"></div>
            </div>
            <div class="q-bank-row">
                <div class="q-bank-label">Account No. :</div>
                <div class="q-bank-val">123-4-56789-0</div>
            </div>
            <div class="q-bank-row">
                <div class="q-bank-label">Account Name :</div>
                <div class="q-bank-val">Tomas Tech Co., Ltd.</div>
            </div>
        </div>
    </div>

    <!-- ===== SIGNATURES ===== -->
    <?php
    $isApproved = ($h['status'] ?? '') === 'APPROVED';
    $stampPath = __DIR__ . '/../../public/assets/approver_sign.png';
    $hasStamp = $isApproved && file_exists($stampPath);
    ?>
    <?php $signerName = $isApproved && !empty($h['approved_by_name']) ? $h['approved_by_name'] : $preparedBy; ?>
    <div class="q-signatures">
        <div class="q-sig-box" style="position:relative;">
            <?php if ($hasStamp): ?>
                <img src="/assets/approver_sign.png" alt="Approved"
                     style="position:absolute; top:-20px; left:50%; transform:translateX(-50%); height:70px; mix-blend-mode:multiply;">
            <?php endif; ?>
            <div class="q-sig-line"></div>
            <div class="q-sig-name"><?= e($signerName) ?></div>
            <div class="q-sig-company">TOMAS TECH CO.,LTD.</div>
            <div class="q-sig-role">Issued by / ผู้เสนอราคา</div>
            <?php if ($isApproved && !empty($h['approved_at'])): ?>
                <div style="font-size:10px;color:#555;margin-top:3px;">Approved: <?= e(substr($h['approved_at'],0,10)) ?></div>
            <?php endif; ?>
        </div>
        <div class="q-sig-box">
            <div class="q-sig-line"></div>
            <div class="q-sig-name"><?= e($h['customer_name'] ?? '') ?></div>
            <div class="q-sig-company">Accepted & Confirmed / ยอมรับและยืนยัน</div>
            <div class="q-sig-date">Date:</div>
        </div>
    </div>

</div>

</div><!-- /preview-paper -->
</div><!-- /preview-wrapper -->
</body></html>
