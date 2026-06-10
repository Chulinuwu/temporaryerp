<?php
/**
 * PEGASUS ERP - Helper Functions
 */

// ── Internationalization (i18n) ──

/** @var array|null Cached translations */
$_PEGASUS_LANG = null;

/**
 * Get current language code
 */
function currentLang(): string
{
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Load and cache translation file for current language
 */
function loadTranslations(): array
{
    global $_PEGASUS_LANG;
    if ($_PEGASUS_LANG !== null) {
        return $_PEGASUS_LANG;
    }
    $lang = currentLang();
    $file = __DIR__ . '/../lang/' . $lang . '.php';
    if (!file_exists($file)) {
        $file = __DIR__ . '/../lang/en.php';
    }
    $_PEGASUS_LANG = require $file;
    return $_PEGASUS_LANG;
}

/**
 * Translate a key. Usage: __('dashboard') or __('footer_text', date('Y'))
 */
function __(string $key, ...$args): string
{
    $translations = loadTranslations();
    $text = $translations[$key] ?? $key;
    if (!empty($args)) {
        $text = sprintf($text, ...$args);
    }
    return $text;
}

/**
 * Translate and HTML-escape. Usage: _e('dashboard')
 */
function _e(string $key, ...$args): string
{
    return htmlspecialchars(__($key, ...$args), ENT_QUOTES, 'UTF-8');
}

/**
 * Format money with thousands separator
 * Default currency is THB (Thai Baht)
 */
function formatMoney($amount, $currency = 'THB')
{
    $formatted = number_format((float) $amount, 2, '.', ',');

    $symbols = [
        'THB' => "\u{0E3F}",
        'USD' => '$',
        'EUR' => "\u{20AC}",
        'JPY' => "\u{00A5}",
    ];

    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . $formatted;
}

/**
 * Format a date string
 */
function formatDate($date, $format = 'Y-m-d')
{
    if (empty($date)) {
        return '';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Convert a Gregorian date to Thai Buddhist Era (BE = CE + 543)
 * Returns formatted string like "13/04/2569"
 */
function toThaiBE($date, $format = 'd/m/Y')
{
    if (empty($date)) {
        return '';
    }
    try {
        $dt = new DateTime($date);
        $year = (int) $dt->format('Y') + 543;
        $formatted = $dt->format($format);
        // Replace the Gregorian year with the BE year
        return str_replace($dt->format('Y'), (string) $year, $formatted);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Generate a document number using the number_sequences table
 * $seqName: sequence name (e.g. 'QUOTATION', 'SALES_ORDER', 'PURCHASE_ORDER')
 * Returns formatted document number
 */
function generateDocNo($seqName, $customDate = null)
{
    $db = Database::getInstance();

    $seq = $db->fetch("SELECT * FROM number_sequences WHERE seq_name = ?", [$seqName]);

    if (!$seq) {
        // Fallback: simple prefix + date + counter
        $datePart = date('ymd');
        return strtoupper(substr($seqName, 0, 2)) . $datePart . '-01';
    }

    $prefix = $seq['prefix'];
    $currentNo = (int)$seq['current_no'] + 1;
    $pattern = $seq['format_pattern'];
    $now = $customDate ? new DateTime($customDate) : new DateTime();

    // Build document number from pattern
    $docNo = $pattern;
    $docNo = str_replace('{PREFIX}', $prefix, $docNo);
    $docNo = str_replace('{YYYY}', $now->format('Y'), $docNo);
    $docNo = str_replace('{YY}', $now->format('y'), $docNo);
    $docNo = str_replace('{MM}', $now->format('m'), $docNo);
    $docNo = str_replace('{DD}', $now->format('d'), $docNo);
    $docNo = str_replace('{YYYYMM}', $now->format('Ym'), $docNo);
    $docNo = str_replace('{YYMMDD}', $now->format('ymd'), $docNo);
    $docNo = str_replace('{NNNNNN}', str_pad($currentNo, 6, '0', STR_PAD_LEFT), $docNo);
    $docNo = str_replace('{NNNN}', str_pad($currentNo, 4, '0', STR_PAD_LEFT), $docNo);
    $docNo = str_replace('{NN}', str_pad($currentNo, 2, '0', STR_PAD_LEFT), $docNo);

    // Update the sequence counter
    $db->query(
        "UPDATE number_sequences SET current_no = ?, fiscal_month = ? WHERE seq_name = ?",
        [$currentNo, (int)$now->format('m'), $seqName]
    );

    return $docNo;
}

/**
 * Sanitize input for XSS prevention
 */
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a CSRF token and store in session
 */
function csrf_token()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Verify a CSRF token
 */
function csrf_verify($token)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Flash message helper
 * Set: flash('success', 'Item saved.')
 * Get: flash('success') => returns the message and removes it
 */
function flash($key, $value = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($value !== null) {
        // Set the flash message
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    // Get and remove the flash message
    if (isset($_SESSION['_flash'][$key])) {
        $msg = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }

    return null;
}

/**
 * Retrieve old input value (for repopulating forms after validation failure)
 */
function old($key, $default = '')
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['_old_input'][$key])) {
        $val = $_SESSION['_old_input'][$key];
        unset($_SESSION['_old_input'][$key]);
        return $val;
    }

    return $default;
}

/**
 * Shortcut for htmlspecialchars with ENT_QUOTES and UTF-8
 */
function e($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

/**
 * Get the localised name for a record (supplier, customer, etc.)
 * Checks current language first, then falls back through other languages.
 * @param array $row  DB row containing name, name_jp, name_th columns
 * @param string $prefix  Column prefix: 'supplier_name', 'customer_name', etc.
 * @return string
 */
function localizedName(array $row, string $prefix = 'supplier_name'): string
{
    $lang = currentLang();
    $map = [
        'ja' => $prefix . '_jp',
        'th' => $prefix . '_th',
        'en' => $prefix,
    ];
    // Try current language first
    $col = $map[$lang] ?? $prefix;
    if (!empty($row[$col])) {
        return $row[$col];
    }
    // Fallback chain: en -> jp -> th
    foreach ([$prefix, $prefix . '_jp', $prefix . '_th'] as $fb) {
        if (!empty($row[$fb])) {
            return $row[$fb];
        }
    }
    return $row[$prefix] ?? '';
}

/**
 * Translate text using Google Translate (free endpoint).
 * Uses curl command since PHP may lack openssl/curl extensions.
 * Returns translated text or null on failure.
 */
function googleTranslate(string $text, string $sourceLang, string $targetLang): ?string
{
    if (empty(trim($text))) return null;

    $url = 'https://translate.googleapis.com/translate_a/single?'
        . http_build_query([
            'client' => 'gtx',
            'sl'     => $sourceLang,
            'tl'     => $targetLang,
            'dt'     => 't',
            'q'      => $text,
        ]);

    // Try curl extension first
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    // Try file_get_contents with SSL context
    elseif (ini_get('allow_url_fopen') && extension_loaded('openssl')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 10, 'header' => "User-Agent: Mozilla/5.0\r\n"],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $response = @file_get_contents($url, false, $ctx);
    }
    // Fallback: use curl CLI command via temp file to avoid escapeshellarg issues with UTF-8
    else {
        $tmpFile = tempnam(sys_get_temp_dir(), 'translate_');
        file_put_contents($tmpFile, $url);
        $redirect = (PHP_OS_FAMILY === 'Windows') ? '2>NUL' : '2>/dev/null';
        $escapedTmp = escapeshellarg($tmpFile);
        $response = shell_exec("curl -s --max-time 10 -K- < NUL --url \"$(type {$escapedTmp})\" {$redirect}");
        // Simpler approach: just use the URL file with curl -K
        if (empty($response)) {
            // Write curl config file
            file_put_contents($tmpFile, "url = \"{$url}\"\n");
            $response = shell_exec("curl -s --max-time 10 -K {$escapedTmp} {$redirect}");
        }
        @unlink($tmpFile);
    }

    if (empty($response)) return null;

    $data = json_decode($response, true);
    if (!$data || !is_array($data[0] ?? null)) return null;

    $result = '';
    foreach ($data[0] as $segment) {
        if (isset($segment[0])) {
            $result .= $segment[0];
        }
    }
    return $result ?: null;
}

/**
 * Convert a number to Thai text (for invoices / checks)
 * Handles integers and decimals up to two places (satang)
 */
function numberToThaiText($number)
{
    $thaiDigits = ['', "\u{0E2B}\u{0E19}\u{0E36}\u{0E48}\u{0E07}", "\u{0E2A}\u{0E2D}\u{0E07}", "\u{0E2A}\u{0E32}\u{0E21}", "\u{0E2A}\u{0E35}\u{0E48}", "\u{0E2B}\u{0E49}\u{0E32}", "\u{0E2B}\u{0E01}", "\u{0E40}\u{0E08}\u{0E47}\u{0E14}", "\u{0E41}\u{0E1B}\u{0E14}", "\u{0E40}\u{0E01}\u{0E49}\u{0E32}"];
    $thaiPositions = ['', "\u{0E2A}\u{0E34}\u{0E1A}", "\u{0E23}\u{0E49}\u{0E2D}\u{0E22}", "\u{0E1E}\u{0E31}\u{0E19}", "\u{0E2B}\u{0E21}\u{0E37}\u{0E48}\u{0E19}", "\u{0E41}\u{0E2A}\u{0E19}", "\u{0E25}\u{0E49}\u{0E32}\u{0E19}"];

    $number = (float) $number;
    if ($number == 0) {
        return "\u{0E28}\u{0E39}\u{0E19}\u{0E22}\u{0E4C}\u{0E1A}\u{0E32}\u{0E17}\u{0E16}\u{0E49}\u{0E27}\u{0E19}"; // ศูนย์บาทถ้วน
    }

    $intPart = (int) floor(abs($number));
    $decPart = round((abs($number) - $intPart) * 100);

    $convertGroup = function ($num) use ($thaiDigits, $thaiPositions) {
        if ($num == 0) return '';
        $result = '';
        $str = (string) $num;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $digit = (int) $str[$i];
            $pos = $len - $i - 1;

            if ($digit == 0) continue;

            if ($pos == 0 && $digit == 1 && $len > 1) {
                $result .= "\u{0E40}\u{0E2D}\u{0E47}\u{0E14}"; // เอ็ด
            } elseif ($pos == 1 && $digit == 2) {
                $result .= "\u{0E22}\u{0E35}\u{0E48}"; // ยี่
                $result .= $thaiPositions[$pos];
            } elseif ($pos == 1 && $digit == 1) {
                $result .= $thaiPositions[$pos];
            } else {
                $result .= $thaiDigits[$digit] . $thaiPositions[$pos];
            }
        }
        return $result;
    };

    $text = '';

    if ($intPart > 0) {
        // Handle millions
        if ($intPart >= 1000000) {
            $millions = (int) floor($intPart / 1000000);
            $text .= $convertGroup($millions) . "\u{0E25}\u{0E49}\u{0E32}\u{0E19}"; // ล้าน
            $intPart = $intPart % 1000000;
        }
        $text .= $convertGroup($intPart);
        $text .= "\u{0E1A}\u{0E32}\u{0E17}"; // บาท
    }

    if ($decPart > 0) {
        $text .= $convertGroup($decPart) . "\u{0E2A}\u{0E15}\u{0E32}\u{0E07}\u{0E04}\u{0E4C}"; // สตางค์
    } else {
        $text .= "\u{0E16}\u{0E49}\u{0E27}\u{0E19}"; // ถ้วน
    }

    if ($number < 0) {
        $text = "\u{0E25}\u{0E1A}" . $text; // ลบ
    }

    return $text;
}

/**
 * Generate a URL path to a public asset
 */
function asset($path)
{
    return '/' . ltrim($path, '/');
}

/**
 * Write an application log line. Level: debug|info|warning|error|critical.
 */
function logger(string $level, string $message, array $context = [])
{
    if (class_exists('Logger')) {
        Logger::log($level, $message, $context);
    }
}
