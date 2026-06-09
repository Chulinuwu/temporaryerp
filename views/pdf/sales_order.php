<?php require __DIR__ . '/_layout.php'; ?>

<div class="page">
    <div class="doc-header">
        <div class="company">
            <div class="company-name">Tomas Tech Co., Ltd.</div>
            <div>88/8 Moo 4, Tambon Mabyangporn, Amphoe Pluak Daeng</div>
            <div>Rayong 21140, Thailand</div>
            <div>Tel: +66-38-955-478 | Email: info@tomastc.com</div>
            <div>Tax ID: 0105564000001</div>
        </div>
        <div>
            <div class="doc-title"><?= _e('sales_order') ?></div>
            <div class="doc-no"><?= e($header['so_no'] ?? '') ?></div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3><?= _e('customer') ?></h3>
            <div class="val"><strong><?= e($header['customer_name'] ?? '') ?></strong></div>
            <div class="val"><?= nl2br(e($header['customer_address'] ?? '')) ?></div>
            <?php if (!empty($header['customer_tax_id'])): ?>
                <div class="val">Tax ID: <?= e($header['customer_tax_id']) ?></div>
            <?php endif; ?>
        </div>
        <div class="info-box">
            <h3><?= _e('details') ?></h3>
            <div class="val"><strong><?= _e('order_date') ?>:</strong> <?= e($header['order_date'] ?? '') ?></div>
            <div class="val"><strong><?= _e('quotation_ref') ?>:</strong> <?= e($header['quotation_no'] ?? '-') ?></div>
            <div class="val"><strong><?= _e('currency') ?>:</strong> <?= e($header['currency_code'] ?? 'THB') ?></div>
            <div class="val"><strong><?= _e('status') ?>:</strong> <?= e($header['status'] ?? '') ?></div>
        </div>
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:30px;" class="center">#</th>
                <th><?= _e('description') ?></th>
                <th style="width:60px;" class="right"><?= _e('qty') ?></th>
                <th style="width:40px;" class="center"><?= _e('unit') ?></th>
                <th style="width:90px;" class="right"><?= _e('unit_price') ?></th>
                <th style="width:100px;" class="right"><?= _e('amount') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $line): ?>
                <tr>
                    <td class="center"><?= e($line['line_no']) ?></td>
                    <td><?= e($line['item_description'] ?? '') ?></td>
                    <td class="right"><?= number_format(floatval($line['quantity'] ?? 0), 2) ?></td>
                    <td class="center"><?= e($line['unit'] ?? 'EA') ?></td>
                    <td class="right"><?= number_format(floatval($line['unit_price'] ?? 0), 2) ?></td>
                    <td class="right"><?= number_format(floatval($line['ext_price'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr><td><?= _e('subtotal') ?></td><td><?= number_format(floatval($header['subtotal_thb'] ?? 0), 2) ?></td></tr>
            <tr><td>VAT <?= e($header['vat_rate'] ?? 7) ?>%</td><td><?= number_format(floatval($header['vat_amount'] ?? 0), 2) ?></td></tr>
            <tr class="grand-total">
                <td><?= _e('grand_total') ?></td>
                <td><?= number_format(floatval($header['grand_total_thb'] ?? 0), 2) ?></td>
            </tr>
        </table>
    </div>

    <?php if (($header['currency_code'] ?? 'THB') === 'THB'): ?>
        <div class="amount-words">
            <strong><?= _e('amount_in_words') ?>:</strong> <?= numberToThaiText(floatval($header['grand_total_thb'] ?? 0)) ?>
        </div>
    <?php endif; ?>

    <div class="signatures">
        <div class="sig-box"><div class="sig-line"><?= _e('authorized_by') ?></div></div>
        <div class="sig-box"><div class="sig-line"><?= _e('received_by') ?></div></div>
    </div>
</div>

</div><!-- /preview-paper -->
</div><!-- /preview-wrapper -->
</body></html>
