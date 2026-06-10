<?php
/**
 * PEGASUS ERP - File logger
 * Daily-rotated, timestamped logs with request correlation. See reference/logging.md.
 */

class Logger
{
    private const LEVELS = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3, 'CRITICAL' => 4];

    private static $dir;
    private static $retentionDays = 7;
    private static $minLevel = 'DEBUG';
    private static $tz;
    private static $requestId = '--------';
    private static $start;
    private static $booted = false;

    public static function init(array $config): void
    {
        if (self::$booted) {
            return;
        }
        self::$dir = rtrim($config['dir'], '/');
        self::$retentionDays = (int)($config['retention_days'] ?? 7);
        self::$minLevel = strtoupper($config['min_level'] ?? 'DEBUG');
        try {
            self::$tz = new DateTimeZone($config['timezone'] ?? 'UTC');
        } catch (Exception $e) {
            self::$tz = new DateTimeZone('UTC');
        }
        self::$requestId = bin2hex(random_bytes(4));

        if (!is_dir(self::$dir)) {
            @mkdir(self::$dir, 0775, true);
        }
        // Route native error_log() (used across controllers) into today's file too.
        ini_set('error_log', self::file());
        self::$booted = true;
    }

    public static function requestId(): string
    {
        return self::$requestId;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);
        if (!self::$booted || !self::passesLevel($level)) {
            return;
        }

        $file = self::file();
        // A brand-new day file means rollover: prune expired logs first (runs ~once/day).
        if (!file_exists($file)) {
            self::purgeExpired();
        }

        $line = sprintf(
            '[%s] [%-8s] [req:%s] [user:%s] %s',
            self::now()->format('Y-m-d H:i:s.v'),
            $level,
            self::$requestId,
            $_SESSION['user_id'] ?? '-',
            $message
        );
        if ($context) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function debug(string $m, array $c = []): void    { self::log('DEBUG', $m, $c); }
    public static function info(string $m, array $c = []): void     { self::log('INFO', $m, $c); }
    public static function warning(string $m, array $c = []): void  { self::log('WARNING', $m, $c); }
    public static function error(string $m, array $c = []): void    { self::log('ERROR', $m, $c); }
    public static function critical(string $m, array $c = []): void { self::log('CRITICAL', $m, $c); }

    public static function exception(Throwable $e, string $prefix = 'Exception'): void
    {
        self::log('ERROR', $prefix . ': ' . get_class($e) . ': ' . $e->getMessage(), [
            'at'    => $e->getFile() . ':' . $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString()),
        ]);
    }

    /**
     * Wire global handlers + request line. Call once at bootstrap.
     */
    public static function registerHandlers(): void
    {
        self::$start = microtime(true);

        set_exception_handler(static function (Throwable $e) {
            self::exception($e, 'Uncaught');
        });

        set_error_handler(static function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false; // @-suppressed; ignore
            }
            self::log('WARNING', 'PHP: ' . $message, ['at' => $file . ':' . $line]);
            return false; // let PHP's normal handling continue
        });

        register_shutdown_function(static function () {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::log('CRITICAL', 'Fatal: ' . $err['message'], ['at' => $err['file'] . ':' . $err['line']]);
            }
            self::logResponse();
        });
    }

    public static function logRequest(): void
    {
        if (empty($_SERVER['REQUEST_METHOD'])) {
            return;
        }
        $ctx = ['ip' => $_SERVER['REMOTE_ADDR'] ?? '-'];
        if (!empty($_GET)) {
            $ctx['query'] = self::redact($_GET);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $ctx['post'] = self::redact($_POST);
        }
        self::info('--> ' . $_SERVER['REQUEST_METHOD'] . ' ' . self::path(), $ctx);
    }

    private static function logResponse(): void
    {
        if (self::$start === null || empty($_SERVER['REQUEST_METHOD'])) {
            return;
        }
        $code = http_response_code() ?: 0;
        $level = $code >= 500 ? 'ERROR' : ($code >= 400 ? 'WARNING' : 'INFO');
        $ms = round((microtime(true) - self::$start) * 1000, 1);
        self::log($level, sprintf('<-- %d %s (%sms)', $code, self::path(), $ms));
    }

    private static function now(): DateTime
    {
        return new DateTime('now', self::$tz);
    }

    private static function file(): string
    {
        return self::$dir . '/' . strtolower(self::now()->format('j_M_Y')) . '.log';
    }

    private static function path(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    private static function passesLevel(string $level): bool
    {
        return (self::LEVELS[$level] ?? 0) >= (self::LEVELS[self::$minLevel] ?? 0);
    }

    private static function redact(array $data): array
    {
        foreach ($data as $k => $v) {
            if (preg_match('/pass|password|pwd|token|secret|csrf/i', (string)$k)) {
                $data[$k] = '***';
            }
        }
        return $data;
    }

    private static function purgeExpired(): void
    {
        $cutoff = time() - self::$retentionDays * 86400;
        foreach (glob(self::$dir . '/*.log') ?: [] as $f) {
            if (filemtime($f) < $cutoff) {
                @unlink($f);
            }
        }
    }
}
