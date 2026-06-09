# Converts USER_MANUAL_TH.md → USER_MANUAL_TH.docx via Word COM
$mdPath   = "$PSScriptRoot\USER_MANUAL_TH.md"
$htmlPath = "$PSScriptRoot\USER_MANUAL_TH_tmp.html"
$docxPath = "$PSScriptRoot\USER_MANUAL_TH.docx"

$md = Get-Content -Raw -Encoding UTF8 $mdPath

# Very light MD → HTML converter
$out = @'
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>PEGASUS ERP - คู่มือผู้ใช้งาน</title>
<style>
body { font-family:'Noto Sans Thai','Leelawadee UI','Segoe UI',Arial,sans-serif; font-size:11pt; line-height:1.55; }
h1 { color:#1565C0; border-bottom:2px solid #1565C0; padding-bottom:4px; font-size:20pt; page-break-before:always; }
h1:first-of-type { page-break-before:auto; }
h2 { color:#1976D2; border-bottom:1px solid #90CAF9; padding-bottom:2px; font-size:15pt; margin-top:18pt; }
h3 { color:#1976D2; font-size:12pt; margin-top:12pt; }
h4 { color:#424242; font-size:11pt; }
table { border-collapse:collapse; width:100%; margin:8pt 0; }
th, td { border:1px solid #BDBDBD; padding:4px 8px; text-align:left; font-size:10pt; vertical-align:top; }
th { background:#E3F2FD; color:#0D47A1; }
code { font-family:Consolas,monospace; background:#F5F5F5; padding:1px 4px; border-radius:2px; font-size:9.5pt; }
pre { background:#F5F5F5; border-left:3px solid #1976D2; padding:8px 12px; font-family:Consolas,monospace; font-size:9pt; overflow-x:auto; }
ul, ol { margin-top:4pt; margin-bottom:4pt; }
hr { border:none; border-top:1px solid #E0E0E0; margin:12pt 0; }
.cover { text-align:center; padding:40pt 20pt; }
</style></head><body>
'@

$inTable = $false
$inList = $false
$inCode = $false
$paragraph = ""

function Flush-Para {
    if ($script:paragraph.Length -gt 0) {
        $script:out += "<p>$($script:paragraph)</p>`n"
        $script:paragraph = ""
    }
}

function Html-Escape($s) {
    return ($s -replace '&','&amp;' -replace '<','&lt;' -replace '>','&gt;')
}

function Inline-Md($s) {
    # Bold
    $s = [regex]::Replace($s, '\*\*(.+?)\*\*', '<strong>$1</strong>')
    # Inline code
    $s = [regex]::Replace($s, '`([^`]+)`', '<code>$1</code>')
    # Links [text](url)
    $s = [regex]::Replace($s, '\[([^\]]+)\]\(([^\)]+)\)', '<a href="$2">$1</a>')
    return $s
}

foreach ($rawLine in $md -split "`n") {
    $line = $rawLine.TrimEnd()

    if ($line -match '^```') {
        if ($inCode) { $out += "</pre>`n"; $inCode = $false }
        else { Flush-Para; $out += "<pre>"; $inCode = $true }
        continue
    }
    if ($inCode) {
        $out += (Html-Escape $line) + "`n"
        continue
    }

    if ($line -match '^\| (.+) \|$') {
        # Table row
        $cells = $Matches[1] -split '\s*\|\s*'
        if ($cells[0] -match '^[\-\:]+$') {
            # separator row — ignore
            continue
        }
        if (-not $inTable) {
            Flush-Para
            $out += "<table>`n"
            $inTable = $true
            $first = $true
        }
        if ($first) {
            $out += "<tr>"
            foreach ($c in $cells) { $out += "<th>" + (Inline-Md (Html-Escape $c.Trim())) + "</th>" }
            $out += "</tr>`n"
            $first = $false
        } else {
            $out += "<tr>"
            foreach ($c in $cells) { $out += "<td>" + (Inline-Md (Html-Escape $c.Trim())) + "</td>" }
            $out += "</tr>`n"
        }
        continue
    } elseif ($inTable) {
        $out += "</table>`n"
        $inTable = $false
    }

    if ($line -match '^# (.+)') { Flush-Para; $out += "<h1>$(Inline-Md (Html-Escape $Matches[1]))</h1>`n" }
    elseif ($line -match '^## (.+)') { Flush-Para; $out += "<h2>$(Inline-Md (Html-Escape $Matches[1]))</h2>`n" }
    elseif ($line -match '^### (.+)') { Flush-Para; $out += "<h3>$(Inline-Md (Html-Escape $Matches[1]))</h3>`n" }
    elseif ($line -match '^#### (.+)') { Flush-Para; $out += "<h4>$(Inline-Md (Html-Escape $Matches[1]))</h4>`n" }
    elseif ($line -match '^[\-\*] (.+)') {
        Flush-Para
        if (-not $inList) { $out += "<ul>`n"; $inList = 'ul' }
        $out += "<li>$(Inline-Md (Html-Escape $Matches[1]))</li>`n"
    }
    elseif ($line -match '^\d+\. (.+)') {
        Flush-Para
        if (-not $inList) { $out += "<ol>`n"; $inList = 'ol' }
        $out += "<li>$(Inline-Md (Html-Escape $Matches[1]))</li>`n"
    }
    elseif ($line -match '^---$') { Flush-Para; if ($inList) { $out += "</$inList>`n"; $inList = $false }; $out += "<hr>`n" }
    elseif ($line -eq '') {
        Flush-Para
        if ($inList) { $out += "</$inList>`n"; $inList = $false }
    }
    else {
        if ($inList) { $out += "</$inList>`n"; $inList = $false }
        if ($script:paragraph) { $script:paragraph += " " }
        $script:paragraph += (Inline-Md (Html-Escape $line))
    }
}
Flush-Para
if ($inList) { $out += "</$inList>`n" }
if ($inTable) { $out += "</table>`n" }
$out += "</body></html>"

$out | Out-File -FilePath $htmlPath -Encoding UTF8

# Convert HTML → DOCX via Word COM
$w = New-Object -ComObject Word.Application
$w.Visible = $false
$doc = $w.Documents.Open($htmlPath)
$doc.SaveAs([ref]$docxPath, [ref]16)
$doc.Close()
$w.Quit()
Remove-Item $htmlPath -Force -ErrorAction SilentlyContinue
Write-Host "DOCX saved: $docxPath"
