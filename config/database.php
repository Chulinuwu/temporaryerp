<?php
/**
 * PEGASUS ERP - Database Configuration
 * PostgreSQL connection settings
 */

return [
    'driver'   => 'pgsql',
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('DB_PORT') ?: '5432',
    'database' => getenv('DB_NAME') ?: 'pegasus_erp',
    'username' => getenv('DB_USER') ?: 'postgres',
    'password' => getenv('DB_PASS') ?: 'postgres',
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
