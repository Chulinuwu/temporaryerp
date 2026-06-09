<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($pageTitle ?? 'Document') ?></title>
<style>
/* ── Reset ── */
* { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Print settings ── */
@page { size: A4; margin: 10mm 12mm 15mm 12mm; }

@media print {
    body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .preview-toolbar { display: none !important; }
    .preview-wrapper { background: #fff !important; padding: 0 !important; min-height: auto !important; }
    .preview-paper { box-shadow: none !important; margin: 0 !important; padding: 10mm 12mm 15mm 12mm !important; max-width: none !important; border-radius: 0 !important; }
}

/* ── Screen: Preview Layout ── */
@media screen {
    body {
        font-family: 'Segoe UI', 'Noto Sans JP', 'Noto Sans Thai', Arial, sans-serif;
        font-size: 10px; color: #333; line-height: 1.45;
        background: #e8ecf0;
        margin: 0; padding: 0;
    }

    /* Top toolbar */
    .preview-toolbar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        background: #1a2332;
        color: #fff;
        padding: 0 24px;
        height: 48px;
        display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        font-family: 'Segoe UI', Arial, sans-serif;
    }
    .preview-toolbar-left {
        display: flex; align-items: center; gap: 12px;
        font-size: 14px; font-weight: 500;
    }
    .preview-toolbar-left .doc-icon {
        width: 28px; height: 28px; background: #003366; border-radius: 4px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; color: #fff;
    }
    .preview-toolbar-right {
        display: flex; align-items: center; gap: 10px;
    }
    .btn-print {
        background: #fff; color: #1a2332; border: none;
        padding: 8px 20px; border-radius: 6px;
        font-weight: 600; cursor: pointer; font-size: 13px;
        display: flex; align-items: center; gap: 6px;
        transition: background 0.15s;
    }
    .btn-print:hover { background: #e3f2fd; }
    .btn-close {
        background: #dc3545; color: #fff; border: none;
        padding: 8px 20px; border-radius: 6px;
        font-weight: 600; cursor: pointer; font-size: 13px;
        display: flex; align-items: center; gap: 6px;
        text-decoration: none;
        transition: background 0.15s;
    }
    .btn-close:hover { background: #c82333; color: #fff; }

    /* Paper area */
    .preview-wrapper {
        padding: 68px 40px 40px 40px;
        min-height: 100vh;
        display: flex; justify-content: center;
    }
    .preview-paper {
        background: #fff;
        width: 210mm; min-height: 297mm;
        max-width: 800px;
        padding: 12mm 14mm 16mm 14mm;
        box-shadow: 0 4px 24px rgba(0,0,0,0.15), 0 1px 4px rgba(0,0,0,0.08);
        border-radius: 2px;
        position: relative;
    }
}

/* ── Common document styles ── */
body { font-family: 'Segoe UI', 'Noto Sans JP', 'Noto Sans Thai', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.45; }
.page { position: relative; }
</style>
</head>
<body>

<!-- Toolbar -->
<div class="preview-toolbar">
    <div class="preview-toolbar-left">
        <div class="doc-icon">T</div>
        <span><?= e($pageTitle ?? 'Document') ?></span>
    </div>
    <div class="preview-toolbar-right">
        <button class="btn-print" onclick="window.print()">&#128424; Print / Save PDF</button>
        <button type="button" class="btn-close" onclick="closePdfPreview()">&times; Close</button>
    </div>
</div>
<script>
function closePdfPreview() {
    // Try to close the tab first (works for tabs opened via window.open or target="_blank")
    window.close();
    // Fallback: if still open, go back in history
    setTimeout(function() {
        if (!window.closed) {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Final fallback: go to dashboard
                window.location.href = '/dashboard';
            }
        }
    }, 150);
}
</script>

<!-- Paper Preview -->
<div class="preview-wrapper">
<div class="preview-paper">
