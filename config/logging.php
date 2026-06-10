<?php
/**
 * PEGASUS ERP - Logging configuration
 */

return [
    'dir'            => getenv('LOG_DIR') ?: (dirname(__DIR__) . '/logs'),
    'retention_days' => (int)(getenv('LOG_RETENTION_DAYS') ?: 7),
    'min_level'      => getenv('LOG_LEVEL') ?: 'DEBUG',
    'timezone'       => getenv('LOG_TZ') ?: 'Asia/Bangkok',
];
