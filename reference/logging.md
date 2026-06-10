# Logging reference

Hand-rolled file logger. Code in `core/Logger.php`, config in `config/logging.php`.

## Where logs go

Daily-rotated files under `logs/` (gitignored), named like `10_jun_2026.log`
(`<day>_<mon>_<year>`, lowercase). One file per calendar day in the configured timezone.

## Line format

```
[2026-06-10 17:22:36.803] [INFO    ] [req:d1b90635] [user:56] Login success | {"user_id":56,"role":"ADMIN"}
```

`[timestamp ms] [LEVEL] [req:id] [user:id] message | {json context}`

- `req:id` correlates every line of one request (the `--> ... / <-- ...` pair share it).
- `user:id` is the session user, or `-` when unauthenticated.
- Timestamps use `config.timezone` (default `Asia/Bangkok`), set on the Logger only -
  the app's global timezone is left untouched.

## What is logged automatically

| Source | Level | Notes |
|---|---|---|
| Request start | INFO | `--> METHOD /path` + ip, query, POST body (passwords/tokens/csrf redacted) |
| Request end | INFO / WARNING / ERROR | `<-- code /path (ms)`; 4xx -> WARNING, 5xx -> ERROR |
| Uncaught exception | ERROR | class, message, file:line, stack trace |
| PHP warning/notice | WARNING | via `set_error_handler` (still bubbles to PHP) |
| Fatal error | CRITICAL | via shutdown handler |
| SQL error | ERROR | `core/Database.php` logs message + sql + params, then rethrows |
| Login / logout | INFO / WARNING | success, failure (with reason), logout |

Wiring lives at the top of `public/index.php` (`Logger::init` -> `registerHandlers` ->
`logRequest`). Bootstrapped before the other `core/` files so setup errors are captured.

## Manual logging

From anywhere:

```php
Logger::debug('message', ['key' => 'value']);   // also info/warning/error/critical
Logger::exception($e, 'Caught');                 // logs class/message/location/trace
logger('info', 'message', ['key' => 'value']);   // procedural helper (core/Helpers.php)
```

## Config

`config/logging.php` (env overrides in parentheses):

| Key | Default | Env |
|---|---|---|
| `dir` | `<project>/logs` | `LOG_DIR` |
| `retention_days` | `7` | `LOG_RETENTION_DAYS` |
| `min_level` | `DEBUG` | `LOG_LEVEL` |
| `timezone` | `Asia/Bangkok` | `LOG_TZ` |

Levels in order: DEBUG < INFO < WARNING < ERROR < CRITICAL. Lines below `min_level` are dropped.

## Retention

On the first write of a new day (when that day's file does not yet exist), the logger
deletes any `logs/*.log` whose mtime is older than `retention_days`. Runs roughly once per
day, no cron needed.

## Notes

- Legacy `error_log()` calls (scattered across ~30 controllers) are redirected into the
  same daily file via `ini_set('error_log', ...)`, so they appear too - but with PHP's own
  bracket format and UTC time, not the structured line above. New code should prefer
  `Logger::` / `logger()`.
- Static asset requests served by `php -S` return before bootstrap, so they are not logged.
