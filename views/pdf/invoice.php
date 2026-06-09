<?php
/**
 * Tax Invoice / Receipt PDF — bilingual Thai/English layout
 * Matches sample: docs/IV-20260400018.pdf
 */
require __DIR__ . '/_layout.php';

// Thai Buddhist year conversion (e.g. 2026 → 2569)
$toBE = fn($d) => $d ? (date('d/m/', strtotime($d)) . (intval(date('Y', strtotime($d))) + 543)) : '';

$installmentNote = '';
if (!empty($header['installment_pct'])) {
    $pct = number_format(floatval($header['installment_pct']), 0);
    $installmentNote = $pct . '%';
    if (!empty($header['installment_desc'])) {
        $installmentNote .= ' — ' . $header['installment_desc'];
    } elseif (!empty($header['installment_trigger'])) {
        $installmentNote .= ' (' . $header['installment_trigger'] . ')';
    }
}
?>
<style>
@page { size: A4; margin: 8mm 10mm; }
@media print {
    html, body { margin:0 !important; padding:0 !important; }
    /* Hide Chrome's default header/footer — user can toggle via print dialog */
}
html, body { margin:0; padding:0; }
.tx-page { font-family: 'Segoe UI', 'Noto Sans Thai', 'Noto Sans JP', Arial, sans-serif;
           font-size: 9.5px; color:#1A237E;
           position:relative; width:190mm; box-sizing:border-box;
           page-break-after:avoid; }
.tx-fill { display:none; }
.tx-page, .tx-page *, .tx-page table, .tx-page td, .tx-page th { color:#1A237E !important; }
.tx-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; border-bottom:2px solid #1A237E; padding-bottom:8px; }
.tx-header-left { display:flex; gap:10px; align-items:flex-start; }
.tx-logo img { width:100px; height:100px; object-fit:contain; }
.tx-company-th { font-size:16px; font-weight:700; line-height:1.3; }
.tx-company-en { font-size:13px; font-weight:700; line-height:1.3; }
.tx-company-address { font-size:10px; line-height:1.4; margin-top:3px; }
.tx-header-right { text-align:right; }
.tx-doc-type-box { border:1.5px solid #1A237E; padding:6px 12px; display:inline-block; margin-bottom:6px; }
.tx-doc-type-th { font-size:13px; font-weight:700; }
.tx-doc-type-en { font-size:10px; font-weight:600; }
.tx-for-account { font-size:9px; margin-top:4px; font-weight:700; }
.tx-note-right { font-size:9px; margin-top:4px; }

.tx-customer-row { display:flex; gap:8px; margin-bottom:8px; }
.tx-customer-box { flex:2; border:1px solid #1A237E; padding:6px 8px; font-size:10.5px; }
.tx-customer-box .row { display:flex; margin-bottom:2px; }
.tx-customer-box .lbl { width:90px; font-weight:600; }
.tx-customer-box .lbl small { font-weight:400; font-size:9px; display:block; }
.tx-customer-box .val { flex:1; font-weight:600; }
.tx-meta-box { flex:1; border:1px solid #1A237E; padding:6px 8px; font-size:10.5px; }
.tx-meta-box .mrow { display:flex; margin-bottom:4px; }
.tx-meta-box .mlbl { width:60px; font-size:9px; }
.tx-meta-box .mlbl small { display:block; font-weight:400; }
.tx-meta-box .mval { flex:1; font-weight:700; font-size:11px; }

.tx-table { width:100%; border-collapse:collapse; margin-bottom:8px; }
.tx-table th, .tx-table td { border:1px solid #1A237E; padding:5px 7px; vertical-align:top; }
.tx-table thead th { background:#fff; text-align:center; font-weight:700; font-size:10px; }
.tx-table thead th small { display:block; font-weight:400; font-size:9px; }
.tx-table td.right { text-align:right; }
.tx-table td.center { text-align:center; }
.tx-table tbody tr.spacer td { display:none; }

.tx-footer { display:flex; gap:6px; margin-top:0; }
.tx-payment-box { flex:3; border:1px solid #1A237E; padding:6px 8px; font-size:10px; }
.tx-payment-box .hdr { font-weight:700; margin-bottom:4px; font-size:10.5px; }
.tx-payment-box .pm-row { display:flex; gap:20px; align-items:center; margin-bottom:4px; }
.tx-payment-box .chk { display:inline-block; width:11px; height:11px; border:1px solid #1A237E; margin-right:4px; vertical-align:middle; }
.tx-amount-words { margin-top:6px; font-size:10px; border:1px solid #1A237E; padding:5px 8px; background:#F3F6FA; font-weight:700; text-align:center; }
.tx-totals-box { flex:2; }
.tx-totals-table { width:100%; border-collapse:collapse; font-size:10.5px; }
.tx-totals-table td { border:1px solid #1A237E; padding:4px 8px; }
.tx-totals-table td.lbl { width:55%; font-weight:600; }
.tx-totals-table td.lbl small { display:block; font-size:9px; font-weight:400; }
.tx-totals-table td.val { text-align:right; font-weight:700; }
.tx-totals-table tr.grand td { background:#1A237E0A; font-size:11.5px; }

.tx-sig-row { display:flex; gap:6px; font-size:9px; text-align:center;
              margin-top:10mm; page-break-inside:avoid; page-break-before:avoid; }
.tx-sig-box { break-inside:avoid; }
.tx-sig-box { flex:1; padding:6px 4px; }
.tx-sig-box .line { border-bottom:1px solid #1A237E; margin-top:20px; height:1px; }
.tx-sig-box .line-sig { border-bottom:1px solid #1A237E; margin-top:24px; height:1px; position:relative; }
.tx-sig-box .stamp { position:absolute; bottom:0; left:50%; transform:translateX(-50%); max-height:60px; mix-blend-mode:multiply; }
.tx-sig-box .lbl-th { font-weight:600; margin-top:3px; }
.tx-sig-box .lbl-en { font-size:8px; }
.tx-sig-box .date-line { font-size:8px; margin-top:2px; }

.tx-watermark { position:absolute; top:35%; left:50%; transform:translate(-50%,-50%) rotate(-20deg); opacity:0.05; pointer-events:none; z-index:0; }
.tx-watermark img { width:340px; }
.tx-page > * { position:relative; z-index:1; }
</style>

<div class="tx-page" style="position:relative;">
    <!-- Header -->
    <div class="tx-header">
        <div class="tx-header-left">
            <div class="tx-logo">
                <img src="/assets/tomas_tech_logo_only.png" alt="Tomas Tech">
            </div>
            <div>
                <div class="tx-company-th">บริษัท โทมัส เทค จำกัด (สำนักงานใหญ่)</div>
                <div class="tx-company-en">TOMAS TECH CO.,LTD. (HEAD OFFICE)</div>
                <div class="tx-company-address">
                    เลขที่ 1 อาคารเอ็มดี ทาวเวอร์ ชั้น 16 ห้อง ซี1 ซอยบางนา-ตราด 25 ถนนเทพรัตน<br>
                    แขวงบางนาเหนือ เขตบางนา กรุงเทพมหานคร 10260<br>
                    โทร. 098-271-9741 เลขประจำตัวผู้เสียภาษี 0115564003364
                </div>
            </div>
        </div>
        <div class="tx-header-right">
            <div class="tx-doc-type-box">
                <div class="tx-doc-type-th">สำเนาใบเสร็จรับเงิน/ใบกำกับภาษี</div>
                <div class="tx-doc-type-en">RECEIPT/TAX INVOICE COPY</div>
            </div>
            <div class="tx-for-account">สำหรับบัญชี<br>FOR ACCOUNT</div>
            <div class="tx-note-right">
                ไม่ใช้ใบกำกับภาษี<br>
                เอกสารออกเป็นชุด
            </div>
        </div>
    </div>

    <!-- Customer + Meta -->
    <div class="tx-customer-row">
        <div class="tx-customer-box">
            <div class="row">
                <div class="lbl">ชื่อลูกค้า<small>Customer</small></div>
                <div class="val"><?= e($header['customer_name'] ?? '') ?></div>
            </div>
            <div class="row">
                <div class="lbl">เลขประจำตัวผู้เสียภาษี<small>Tax ID.</small></div>
                <div class="val"><?= e($header['customer_tax_id'] ?? '-') ?></div>
            </div>
            <div class="row">
                <div class="lbl">ที่อยู่<small>Address</small></div>
                <div class="val"><?= nl2br(e($header['customer_address'] ?? '-')) ?></div>
            </div>
            <?php if (!empty($header['customer_phone'])): ?>
            <div class="row">
                <div class="lbl">Tel :</div>
                <div class="val"><?= e($header['customer_phone']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="tx-meta-box">
            <div class="mrow">
                <div class="mlbl">วันที่<small>Date</small></div>
                <div class="mval"><?= e($toBE($header['invoice_date'] ?? '')) ?></div>
            </div>
            <div class="mrow">
                <div class="mlbl">เลขที่<small>No.</small></div>
                <div class="mval"><?= e($header['invoice_no'] ?? '') ?></div>
            </div>
            <div class="mrow">
                <div class="mlbl">การชำระเงิน<small>Credit Term</small></div>
                <div class="mval">เครดิต <?= e($header['credit_days'] ?? '7') ?> วัน</div>
            </div>
            <div class="mrow">
                <div class="mlbl">ผู้ขาย<small>Salesman</small></div>
                <div class="mval"><?= e($header['salesman_name'] ?? '') ?></div>
            </div>
            <div class="mrow">
                <div class="mlbl">ครบกำหนด<small>Due Date</small></div>
                <div class="mval"><?= e($toBE($header['due_date'] ?? '')) ?></div>
            </div>
            <?php if (!empty($header['so_po_no'])): ?>
            <div class="mrow">
                <div class="mlbl">ใบสั่งซื้อเลขที่<small>P/O No.</small></div>
                <div class="mval"><?= e($header['so_po_no']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lines -->
    <table class="tx-table">
        <thead>
            <tr>
                <th style="width:50px;">รหัส<small>Code</small></th>
                <th>รายละเอียด<small>Description</small></th>
                <th style="width:70px;">จำนวน<small>Quantity</small></th>
                <th style="width:80px;">ราคา/หน่วย<small>Unit Price</small></th>
                <th style="width:70px;">ส่วนลด<small>Discount</small></th>
                <th style="width:90px;">จำนวนเงิน<small>Amount</small></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lines as $line): ?>
            <tr>
                <td class="center"><?= e($line['line_no']) ?></td>
                <td>
                    <?php if (!empty($line['item_code'])): ?><strong><?= e($line['item_code']) ?></strong><br><?php endif; ?>
                    <?= e($line['item_description'] ?? $line['item_name'] ?? '') ?>
                    <?php if (!empty($installmentNote) && $line['line_no'] == 1): ?>
                        <div style="font-size:11px;font-weight:700;margin-top:4px;"><?= e($installmentNote) ?></div>
                    <?php endif; ?>
                </td>
                <td class="center"><?= number_format(floatval($line['quantity'] ?? 0), 2) ?></td>
                <td class="right"><?= number_format(floatval($line['unit_price'] ?? 0), 2) ?></td>
                <td class="right"><?= floatval($line['discount_rate'] ?? 0) > 0 ? number_format(floatval($line['discount_rate']), 0) . '%' : '-' ?></td>
                <td class="right"><?= number_format(floatval($line['ext_price'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <!-- Spacer row to push footer down (like the sample) -->
        <tr class="spacer"><td colspan="6"></td></tr>
        </tbody>
    </table>

    <!-- Footer: payment methods + totals -->
    <div class="tx-footer">
        <div class="tx-payment-box">
            <div class="hdr">การชำระเงิน <span style="font-weight:400;font-size:9px;">Conditions of Payments</span></div>
            <div class="pm-row">
                <span><span class="chk"></span> เงินสด <span style="font-size:9px;">Cash</span></span>
                <span><span class="chk"></span> โอนเงิน <span style="font-size:9px;">Bank Transfer</span></span>
                <span><span class="chk"></span> อื่นๆ <span style="font-size:9px;">Other</span></span>
            </div>
            <div class="pm-row">
                <span><span class="chk"></span> เช็คธนาคาร <span style="font-size:9px;">Cheque Bank</span> …………………………… เลขที่ No. ………………… ลงวันที่ Date ……………</span>
            </div>
            <div style="font-size:9px;margin-top:6px;line-height:1.4;">
                ใบเสร็จรับเงินนี้ จะถือว่าเป็นการถูกต้อง และสมบูรณ์ต่อเมื่อมีลายเซ็นต์ของผู้จัดการและผู้รับเงิน และได้รับเงินเรียบร้อยแล้ว<br>
                กรณีชำระเงินเป็นเช็ค ใบเสร็จจะสมบูรณ์เมื่อเรียกเก็บเงินตามเช็คได้เรียบร้อยแล้ว
            </div>
            <div class="tx-amount-words">
                (<?= e(numberToThaiText(floatval($header['grand_total_thb'] ?? 0))) ?>)
            </div>
        </div>
        <div class="tx-totals-box">
            <table class="tx-totals-table">
                <tr>
                    <td class="lbl">รวมเป็นเงิน<small>Total</small></td>
                    <td class="val"><?= number_format(floatval($header['subtotal_thb'] ?? 0), 2) ?></td>
                </tr>
                <tr>
                    <td class="lbl">หักส่วนลด พิเศษ<small>Special Discount</small></td>
                    <td class="val"><?= floatval($header['special_discount'] ?? 0) > 0 ? number_format(floatval($header['special_discount']), 2) : '-' ?></td>
                </tr>
                <tr>
                    <td class="lbl">ยอดรวมหลังหักส่วนลด<small>Total Discount</small></td>
                    <td class="val"><?= number_format(floatval($header['total_after_discount'] ?? $header['subtotal_thb'] ?? 0), 2) ?></td>
                </tr>
                <tr>
                    <td class="lbl">จำนวนภาษีมูลค่าเพิ่ม <?= e($header['vat_rate'] ?? 7) ?> %<small>Value Added Tax</small></td>
                    <td class="val"><?= number_format(floatval($header['vat_amount'] ?? 0), 2) ?></td>
                </tr>
                <tr class="grand">
                    <td class="lbl">จำนวนเงินรวมทั้งสิ้น<small>Grand Total</small></td>
                    <td class="val"><?= number_format(floatval($header['grand_total_thb'] ?? 0), 2) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Flex spacer pushes signatures to page bottom while keeping 1-page fit -->
    <div class="tx-fill"></div>

    <!-- Signatures (blank — to be signed on paper, matching sample) -->
    <div class="tx-sig-row">
        <div class="tx-sig-box">
            <div class="line"></div>
            <div class="lbl-th">พนักงานเก็บเงิน / Bill Collector</div>
            <div class="date-line">วันที่ / Date …………………………</div>
        </div>
        <div class="tx-sig-box">
            <div class="line"></div>
            <div class="lbl-th">ผู้ส่งสินค้า / Goods Deliver</div>
            <div class="date-line">วันที่ / Date …………………………</div>
        </div>
        <div class="tx-sig-box">
            <div class="line"></div>
            <div class="lbl-th">ผู้รับสินค้า / Goods Receiver</div>
            <div class="date-line">วันที่ / Date …………………………</div>
        </div>
        <div class="tx-sig-box">
            <div class="line"></div>
            <div class="lbl-th">ผู้มีอำนาจลงนาม<br><span class="lbl-en">Authorized Signature</span></div>
        </div>
    </div>
</div>

</div><!-- /preview-paper -->
</div><!-- /preview-wrapper -->
</body></html>
