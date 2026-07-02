<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'afgrigear_shop');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/ecommerce/jirani-e-store/ispman-shop/');

// ── M-Pesa Daraja credentials ──────────────────────────────────────────────
define('MPESA_CONSUMER_KEY',    'YOUR_CONSUMER_KEY_HERE');
define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET_HERE');
define('MPESA_SHORTCODE',       '174379');          // Daraja sandbox shortcode
define('MPESA_PASSKEY',         'YOUR_PASSKEY_HERE');
define('MPESA_CALLBACK_URL',    BASE_URL . 'api/mpesa/callback.php');
define('MPESA_ENV',             'sandbox');         // 'sandbox' or 'live'
define('DELIVERY_FEE',          500);               // KES flat delivery fee

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
