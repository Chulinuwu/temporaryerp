<?php
/**
 * PEGASUS ERP — Sales Customer Detail
 * Variables: $customer, $contacts, $cards, $deals
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($customer['customer_name'] ?? '') ?></h1>
        <div class="breadcrumb">
            <a href="/sales/customers"><?= _e('menu_sales_customers') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($customer['customer_code'] ?? '') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/master/customers/<?= e($customer['customer_id']) ?>/edit" class="btn btn-cancel"><?= _e('edit_master') ?></a>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('contactModal').classList.add('active')">
            + <?= __('new_contact') ?>
        </button>
    </div>
</div>

<!-- Customer info -->
<div class="card" style="padding:16px 20px;margin-bottom:16px;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;font-size:13px;">
        <div><strong><?= _e('code') ?>:</strong> <?= e($customer['customer_code'] ?? '') ?></div>
        <div><strong><?= _e('tax_id') ?>:</strong> <?= e($customer['tax_id'] ?? '—') ?></div>
        <div><strong><?= _e('phone') ?>:</strong> <?= e($customer['phone'] ?? '—') ?></div>
        <div><strong><?= _e('email') ?>:</strong> <?= e($customer['email'] ?? '—') ?></div>
        <div><strong><?= _e('sales_person') ?>:</strong> <?= e($customer['sales_rep_name'] ?? '—') ?></div>
        <div style="grid-column:1/-1;"><strong><?= _e('address') ?>:</strong> <?= nl2br(e($customer['address'] ?? '')) ?></div>
    </div>
</div>

<!-- Contacts section -->
<div class="card" style="padding:16px 20px;margin-bottom:16px;">
    <h2 style="margin-bottom:10px;"><?= __('contacts') ?> (<?= count($contacts) ?>)</h2>
    <?php if (empty($contacts)): ?>
        <p style="color:var(--color-text-muted);font-size:13px;"><?= __('no_contacts_yet') ?></p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;">
        <?php foreach ($contacts as $c): ?>
            <div style="border:1px solid var(--color-border);border-radius:6px;padding:12px;display:flex;gap:10px;">
                <?php if (!empty($c['latest_card'])): ?>
                    <a href="<?= e($c['latest_card_full'] ?? $c['latest_card']) ?>" target="_blank" style="flex:0 0 80px;">
                        <img src="<?= e($c['latest_card']) ?>" style="width:80px;height:50px;object-fit:cover;border:1px solid #ddd;border-radius:3px;">
                    </a>
                <?php else: ?>
                    <div style="flex:0 0 80px;height:50px;background:#ECEFF1;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#90A4AE;">No Card</div>
                <?php endif; ?>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;">
                        <?= e($c['full_name']) ?>
                        <?php if (!empty($c['full_name_local'])): ?>
                            <span style="font-weight:400;font-size:12px;color:var(--color-text-muted);">(<?= e($c['full_name_local']) ?>)</span>
                        <?php endif; ?>
                        <?php if (!empty($c['is_primary'])): ?>
                            <span class="badge" style="background:#FB8C00;color:#fff;font-size:10px;">Primary</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:12px;color:var(--color-text-muted);">
                        <?= e($c['title'] ?? '') ?>
                        <?php if (!empty($c['department'])): ?> — <?= e($c['department']) ?><?php endif; ?>
                    </div>
                    <div style="font-size:11.5px;margin-top:4px;line-height:1.6;">
                        <?php if (!empty($c['email'])): ?><div>&#9993; <?= e($c['email']) ?></div><?php endif; ?>
                        <?php if (!empty($c['phone'])): ?><div>&#9742; <?= e($c['phone']) ?></div><?php endif; ?>
                        <?php if (!empty($c['mobile'])): ?><div>&#128241; <?= e($c['mobile']) ?></div><?php endif; ?>
                        <?php if (!empty($c['fax'])): ?><div>Fax: <?= e($c['fax']) ?></div><?php endif; ?>
                    </div>
                    <div style="margin-top:6px;font-size:11px;">
                        <a href="#" onclick="editContact(<?= (int)$c['contact_id'] ?>); return false;"><?= __('edit') ?></a>
                        &nbsp;|&nbsp;
                        <form method="POST" action="/sales/customers/<?= e($customer['customer_id']) ?>/contacts/<?= e($c['contact_id']) ?>/delete" style="display:inline;">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                style="background:none;border:none;color:#D32F2F;cursor:pointer;padding:0;font-size:11px;">
                                <?= __('delete') ?>
                            </button>
                        </form>
                    </div>
                    <script type="application/json" id="contact-data-<?= (int)$c['contact_id'] ?>"><?= json_encode($c, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Business Cards Gallery -->
<div class="card" style="padding:16px 20px;margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h2><?= __('business_cards') ?> (<?= count($cards) ?>)</h2>
        <form method="POST" action="/sales/customers/<?= e($customer['customer_id']) ?>/cards/upload"
              enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px;" id="quickUploadForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="file" name="card_image" accept="image/*" class="form-input" style="width:220px;font-size:12px;"
                   onchange="if(this.files.length) document.getElementById('quickUploadForm').submit();">
            <span style="font-size:11px;color:var(--color-text-muted);"><?= __('quick_upload_hint') ?></span>
        </form>
    </div>
    <?php if (empty($cards)): ?>
        <p style="color:var(--color-text-muted);font-size:13px;"><?= __('no_cards_yet') ?></p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
        <?php foreach ($cards as $bc): ?>
            <div style="border:1px solid var(--color-border);border-radius:6px;overflow:hidden;background:#FAFAFA;">
                <a href="<?= e($bc['file_path']) ?>" target="_blank" style="display:block;">
                    <img src="<?= e($bc['thumbnail_path'] ?? $bc['file_path']) ?>"
                         style="width:100%;height:140px;object-fit:cover;display:block;">
                </a>
                <div style="padding:8px 10px;font-size:11px;">
                    <?php if (!empty($bc['contact_name'])): ?>
                        <div style="font-weight:600;"><?= e($bc['contact_name']) ?></div>
                    <?php else: ?>
                        <div style="color:var(--color-text-muted);"><?= __('unlinked_card') ?></div>
                    <?php endif; ?>
                    <div style="color:var(--color-text-muted);">
                        <?= e($bc['file_name'] ?? '') ?>
                        <?php if (!empty($bc['file_size'])): ?>
                            (<?= round(($bc['file_size'] ?? 0) / 1024) ?> KB)
                        <?php endif; ?>
                    </div>
                    <div style="margin-top:4px;display:flex;gap:8px;">
                        <a href="<?= e($bc['file_path']) ?>" target="_blank"><?= __('view') ?></a>
                        <form method="POST" action="/sales/customers/<?= e($customer['customer_id']) ?>/cards/<?= e($bc['card_id']) ?>/delete" style="display:inline;">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                style="background:none;border:none;color:#D32F2F;cursor:pointer;padding:0;font-size:11px;">
                                <?= __('delete') ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Deals section -->
<div class="card" style="padding:16px 20px;margin-bottom:16px;">
    <h2 style="margin-bottom:10px;"><?= __('related_deals') ?> (<?= count($deals) ?>)</h2>
    <?php if (empty($deals)): ?>
        <p style="color:var(--color-text-muted);font-size:13px;"><?= __('no_deals_found') ?></p>
    <?php else: ?>
        <table class="data-table">
            <thead><tr>
                <th><?= _e('deal_no') ?></th><th><?= _e('deal_name') ?></th>
                <th class="text-right"><?= _e('amount') ?></th>
                <th class="text-center"><?= _e('possibility') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($deals as $d): ?>
                <tr>
                    <td><a href="/sales/deals/<?= e($d['deal_id']) ?>"><?= e($d['deal_no']) ?></a></td>
                    <td><?= e($d['deal_name']) ?></td>
                    <td class="text-right"><?= formatMoney($d['expected_amount']) ?></td>
                    <td class="text-center">
                        <span class="badge" style="background:<?= e($d['color'] ?? '#757575') ?>;color:#fff;font-size:11px;">
                            <?= e($d['status_name'] ?? '') ?> (<?= intval($d['win_pct'] ?? 0) ?>%)
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ====== Contact Modal (new / edit) ====== -->
<div id="contactModal" class="modal-overlay">
    <div class="modal" style="max-width:900px;">
        <div class="modal-header">
            <h3 id="contactModalTitle"><?= __('new_contact') ?></h3>
            <button type="button" class="modal-close" onclick="closeContactModal()">&times;</button>
        </div>
        <form method="POST" action="/sales/customers/<?= e($customer['customer_id']) ?>/contacts"
              enctype="multipart/form-data" id="contactForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="contact_id" id="f_contact_id" value="">
            <div class="modal-body" style="display:grid;grid-template-columns:340px 1fr;gap:16px;">

                <!-- LEFT: Card upload + preview + OCR -->
                <div>
                    <label class="form-label"><?= __('business_card_image') ?></label>
                    <input type="file" name="card_image" id="cardFile" accept="image/*" class="form-input"
                           onchange="previewCard(this)">
                    <div style="margin-top:8px;text-align:center;background:#F5F5F5;border-radius:4px;padding:6px;">
                        <img id="cardPreview" src="" alt="" style="max-width:100%;max-height:200px;display:none;">
                        <div id="cardPlaceholder" style="color:#9E9E9E;font-size:12px;padding:30px 0;"><?= __('card_preview_hint') ?></div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" style="margin-top:8px;width:100%;"
                            id="ocrBtn" onclick="runOcr()" disabled>
                        &#128269; <?= __('extract_text_ocr') ?>
                    </button>
                    <div id="ocrStatus" style="font-size:11px;color:var(--color-text-muted);margin-top:4px;min-height:16px;"></div>
                    <textarea name="ocr_raw_text" id="ocrRawText" class="form-input"
                              style="margin-top:6px;font-size:11px;height:90px;font-family:Consolas,monospace;"
                              placeholder="<?= __('ocr_raw_text_placeholder') ?>"></textarea>
                </div>

                <!-- RIGHT: Contact form -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div class="form-group">
                        <label class="form-label"><?= __('full_name') ?> *</label>
                        <input type="text" name="full_name" id="f_full_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('full_name_local') ?></label>
                        <input type="text" name="full_name_local" id="f_full_name_local" class="form-input"
                               placeholder="例: 三治 雅人">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('title') ?></label>
                        <input type="text" name="title" id="f_title" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('department') ?></label>
                        <input type="text" name="department" id="f_department" class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label"><?= __('company_name') ?></label>
                        <input type="text" name="company_name" id="f_company_name" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('email') ?></label>
                        <input type="email" name="email" id="f_email" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('phone') ?></label>
                        <input type="text" name="phone" id="f_phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('mobile') ?></label>
                        <input type="text" name="mobile" id="f_mobile" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('fax') ?></label>
                        <input type="text" name="fax" id="f_fax" class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label"><?= _e('address') ?></label>
                        <textarea name="address" id="f_address" class="form-input" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('website') ?></label>
                        <input type="text" name="website" id="f_website" class="form-input">
                    </div>
                    <div class="form-group" style="display:flex;align-items:end;">
                        <label style="font-size:13px;"><input type="checkbox" name="is_primary" id="f_is_primary" value="1"> <?= __('is_primary_contact') ?></label>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label"><?= __('notes') ?></label>
                        <textarea name="notes" id="f_notes" class="form-input" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeContactModal()"><?= _e('cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Tesseract.js for client-side OCR (loaded only when OCR is triggered) -->
<script>
function closeContactModal(){ document.getElementById('contactModal').classList.remove('active'); }

function previewCard(input) {
    const f = input.files[0];
    if (!f) return;
    const url = URL.createObjectURL(f);
    const img = document.getElementById('cardPreview');
    img.src = url;
    img.style.display = 'block';
    document.getElementById('cardPlaceholder').style.display = 'none';
    document.getElementById('ocrBtn').disabled = false;
}

// Parse raw OCR text and populate form fields heuristically
function parseAndFill(raw) {
    const lines = raw.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
    const joined = lines.join('\n');

    const pick = (re) => { const m = joined.match(re); return m ? m[1].trim() : ''; };

    const email  = pick(/([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/);
    const tel    = pick(/(?:TEL|Tel|T\s*:)\s*[:：]?\s*([0-9+\-()\s]{7,})/);
    const mobile = pick(/(?:MOBILE|Mobile|M\s*:)\s*[:：]?\s*([0-9+\-()\s]{7,})/);
    const fax    = pick(/(?:FAX|Fax)\s*[:：]?\s*([0-9+\-()\s]{7,})/);
    const site   = pick(/(https?:\/\/\S+|www\.\S+)/);

    // Heuristic: first non-company upper-case line is often the person name
    let name = '', title = '', dept = '', company = '', addr = '';
    const companyRx = /(CO\.,?\s*LTD|COMPANY|CORP|LIMITED|LTD\.)/i;
    const titleRx = /(PRESIDENT|MANAGER|SUPERVISOR|DIRECTOR|CHIEF|ENGINEER|SALES|MARKETING)/i;
    lines.forEach(l => {
        if (!company && companyRx.test(l)) company = l;
        else if (!title && titleRx.test(l) && l.length < 80) title = l;
        else if (!name && /^[A-Z][A-Za-z .'-]+$/.test(l) && l.split(' ').length <= 4) name = l;
        else if (/\d/.test(l) && l.length > 15 && !addr) addr = l;
    });

    const setIf = (id, val) => { if (val) document.getElementById(id).value = val; };
    setIf('f_full_name', name);
    setIf('f_title', title);
    setIf('f_company_name', company);
    setIf('f_email', email);
    setIf('f_phone', tel);
    setIf('f_mobile', mobile);
    setIf('f_fax', fax);
    setIf('f_website', site);
    setIf('f_address', addr);
}

async function runOcr() {
    const file = document.getElementById('cardFile').files[0];
    if (!file) return;
    const st = document.getElementById('ocrStatus');
    st.textContent = '<?= __('loading_ocr_engine') ?>';

    // Load Tesseract.js on demand from CDN
    if (typeof Tesseract === 'undefined') {
        await new Promise((res, rej) => {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
            s.onload = res; s.onerror = rej;
            document.head.appendChild(s);
        });
    }

    st.textContent = '<?= __('ocr_processing') ?>';
    try {
        const { data } = await Tesseract.recognize(file, 'eng+jpn', {
            logger: m => {
                if (m.status === 'recognizing text' && m.progress) {
                    st.textContent = '<?= __('ocr_processing') ?> ' + Math.round(m.progress * 100) + '%';
                }
            }
        });
        document.getElementById('ocrRawText').value = data.text || '';
        parseAndFill(data.text || '');
        st.textContent = '<?= __('ocr_done') ?>';
    } catch (err) {
        console.error(err);
        st.textContent = '<?= __('ocr_failed') ?>: ' + err.message;
    }
}

function editContact(id) {
    const node = document.getElementById('contact-data-' + id);
    if (!node) return;
    const data = JSON.parse(node.textContent);
    document.getElementById('contactModalTitle').textContent = '<?= __('edit_contact') ?>';
    document.getElementById('f_contact_id').value = id;
    ['full_name','full_name_local','title','department','company_name','email','phone','mobile','fax','address','website','notes']
        .forEach(k => { const el = document.getElementById('f_' + k); if (el) el.value = data[k] || ''; });
    document.getElementById('f_is_primary').checked = !!(data.is_primary === true || data.is_primary === 't' || data.is_primary === 1);
    document.getElementById('contactModal').classList.add('active');
}

// Reset modal when opened via + button
document.querySelector('[onclick*="contactModal"]').addEventListener('click', () => {
    document.getElementById('contactModalTitle').textContent = '<?= __('new_contact') ?>';
    document.getElementById('contactForm').reset();
    document.getElementById('f_contact_id').value = '';
    document.getElementById('cardPreview').style.display = 'none';
    document.getElementById('cardPlaceholder').style.display = 'block';
    document.getElementById('ocrBtn').disabled = true;
    document.getElementById('ocrStatus').textContent = '';
});
</script>
