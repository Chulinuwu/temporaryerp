<?php require __DIR__ . '/_layout.php'; ?>
<style>
/* Bulk PDF - Shared styles from quotation template */
.q-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.q-logo-area { display: flex; align-items: flex-start; gap: 10px; }
.q-logo { width: 44px; height: 44px; background: #003366; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; flex-shrink: 0; }
.q-company-name { font-size: 15px; font-weight: 700; color: #003366; margin-bottom: 1px; }
.q-company-name-th { font-size: 10px; color: #003366; margin-bottom: 3px; }
.q-company-info { font-size: 8.5px; color: #555; line-height: 1.5; }
.q-title-area { text-align: right; }
.q-doc-title { font-size: 26px; font-weight: 700; color: #003366; letter-spacing: 1px; }
.q-doc-title-th { font-size: 12px; color: #003366; margin-bottom: 4px; }
.q-doc-meta { font-size: 9px; color: #666; }

.q-info-section { border: 1.5px solid #003366; margin-bottom: 14px; display: flex; }
.q-info-left { flex: 1; padding: 10px 14px; border-right: 1.5px solid #003366; }
.q-info-right { flex: 1; padding: 10px 14px; }
.q-info-title { font-size: 10px; font-weight: 700; color: #003366; text-decoration: underline; margin-bottom: 6px; }
.q-info-row { display: flex; margin-bottom: 2px; font-size: 10px; }
.q-info-label { width: 90px; font-weight: 600; flex-shrink: 0; }
.q-info-label-r { width: 100px; font-weight: 600; flex-shrink: 0; }
.q-info-val { flex: 1; }

.q-table { width: 100%; border-collapse: collapse; }
.q-table thead th { background: #003366; color: #fff; padding: 6px 8px; font-size: 9.5px; font-weight: 600; text-align: left; }
.q-table thead th.r { text-align: right; }
.q-table thead th.c { text-align: center; }
.q-table tbody td { border-bottom: 0.5px solid #ddd; padding: 5px 8px; font-size: 10px; }
.q-table tbody td.r { text-align: right; }
.q-table tbody td.c { text-align: center; }
.q-table-wrapper { min-height: 200px; border-left: 0.5px solid #ddd; border-right: 0.5px solid #ddd; border-bottom: 0.5px solid #ddd; }

.q-totals { display: flex; justify-content: flex-end; margin-top: 15px; }
.q-totals-table { width: 340px; border-collapse: collapse; }
.q-totals-table td { padding: 3px 10px; font-size: 10px; }
.q-totals-table td:last-child { text-align: right; font-weight: 600; width: 120px; }
.q-totals-table .q-grand-total td { background: #003366; color: #fff; font-size: 11px; font-weight: 700; padding: 6px 10px; }
</style>

<?php foreach ($documents as $idx => $doc):
    $header = $doc['header'];
    $lines = $doc['lines'];
    $isQuotation = ($type === 'quotation');
    $isSO = ($type === 'salesorder');
    $docNo = $isQuotation ? ($header['quotation_no'] ?? '') : ($isSO ? ($header['so_no'] ?? '') : ($header['invoice_no'] ?? ''));
    $docDate = $isQuotation ? ($header['issue_date'] ?? '') : ($isSO ? ($header['order_date'] ?? '') : ($header['invoice_date'] ?? ''));
    $titleEn = $isQuotation ? 'QUOTATION' : ($isSO ? 'SALES ORDER' : 'TAX INVOICE');
    $titleTh = $isQuotation ? 'ใบเสนอราคา' : ($isSO ? 'ใบสั่งขาย' : 'ใบกำกับภาษี');
?>
<div class="page" style="font-size:10px;">
    <div class="q-header">
        <div class="q-logo-area">
            <div class="q-logo">T</div>
            <div>
                <div class="q-company-name">TOMAS TECH CO.,LTD.</div>
                <div class="q-company-name-th">บริษัท โทมัส เทค จำกัด</div>
                <div class="q-company-info">
                    No.1 M/D tower 1/61, Unit C1, Soi Bangna-trad 25, Debaratna Rd.,<br>
                    Khawaeng Bang Na Nuea, Khet Bang Na, Bangkok 10260 Thailand<br>
                    Tel: +66-98-271-9141 | E-mail: info@tomastc.com | Tax ID: 0-1155-64003-36-4
                </div>
            </div>
        </div>
        <div class="q-title-area">
            <div class="q-doc-title"><?= $titleEn ?></div>
            <div class="q-doc-title-th"><?= $titleTh ?></div>
        </div>
    </div>

    <div class="q-info-section">
        <div class="q-info-left">
            <div class="q-info-title">CUSTOMER INFORMATION</div>
            <div class="q-info-row">
                <div class="q-info-label">Company :</div>
                <div class="q-info-val"><strong><?= e($header['customer_name'] ?? '') ?></strong></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label">Address :</div>
                <div class="q-info-val"><?= e($header['customer_address'] ?? '') ?></div>
            </div>
        </div>
        <div class="q-info-right">
            <div class="q-info-title">DOCUMENT DETAILS</div>
            <div class="q-info-row">
                <div class="q-info-label-r">Doc No. :</div>
                <div class="q-info-val"><strong><?= e($docNo) ?></strong></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label-r">Date :</div>
                <div class="q-info-val"><?= e($docDate) ?></div>
            </div>
            <div class="q-info-row">
                <div class="q-info-label-r">Status :</div>
                <div class="q-info-val"><?= e($header['status'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <div class="q-table-wrapper">
        <table class="q-table">
            <thead>
                <tr>
                    <th style="width:35px;" class="c">No.</th>
                    <th>Description</th>
                    <th style="width:60px;" class="r">Qty</th>
                    <th style="width:50px;" class="c">Unit</th>
                    <th style="width:110px;" class="r">Unit Price</th>
                    <th style="width:110px;" class="r">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $n = 0; foreach ($lines as $line):
                    $isCat = !empty($line['is_category_row']);
                    if ($isCat): ?>
                    <tr style="background:#f0f4f8;font-weight:700;">
                        <td class="c"></td>
                        <td colspan="5"><?= e($line['item_description'] ?? '') ?></td>
                    </tr>
                <?php else: $n++; ?>
                    <tr>
                        <td class="c"><?= $n ?></td>
                        <td><?= e($line['item_description'] ?? $line['description'] ?? '') ?></td>
                        <td class="r"><?= number_format(floatval($line['quantity'] ?? 0), 2) ?></td>
                        <td class="c"><?= e($line['unit'] ?? '') ?></td>
                        <td class="r"><?= number_format(floatval($line['unit_price'] ?? 0), 2) ?></td>
                        <td class="r"><?= number_format(floatval($line['ext_price'] ?? $line['amount'] ?? 0), 2) ?></td>
                    </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="q-totals">
        <table class="q-totals-table">
            <tr><td>Subtotal / ยอดรวมก่อนภาษี</td><td><?= number_format(floatval($header['subtotal_thb'] ?? 0), 2) ?></td></tr>
            <tr><td>VAT / ภาษีมูลค่าเพิ่ม</td><td><?= number_format(floatval($header['vat_amount'] ?? 0), 2) ?></td></tr>
            <tr class="q-grand-total">
                <td>GRAND TOTAL / ยอดรวมทั้งสิ้น</td>
                <td><?= number_format(floatval($header['grand_total_thb'] ?? 0), 2) ?></td>
            </tr>
        </table>
    </div>
</div>
<?php endforeach; ?>

</div><!-- /preview-paper -->
</div><!-- /preview-wrapper -->
</body></html>
