<?php
/**
 * PEGASUS ERP - Minimal .env loader
 * Reads KEY=VALUE lines into the process environment. The real OS environment
 * always wins, so .env only fills gaps (config/*.php fall back to hardcoded defaults).
 */

class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            if ($key === '' || getenv($key) !== false) {
                continue; // already set in the real environment; leave it
            }

            $val = trim(substr($line, $pos + 1));
            if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && $val[-1] === $val[0]) {
                $val = substr($val, 1, -1);
            }

            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}
