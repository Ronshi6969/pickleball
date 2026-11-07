<?php
// ---- Edit these to match YOUR SQL Server (see README.md) ----
// Server name examples:
//   'localhost\SQLEXPRESS'   -> SQL Server Express (named instance)  <- most common
//   'localhost'              -> default instance
//   'localhost,1433'         -> specific port
// Use the SAME name you type in the SSMS "Server name" box.
define('DB_SERVER', 'localhost\SQLEXPRESS');
define('DB_NAME', 'sports_hub');

// Leave DB_USER empty ('') to use Windows Authentication (same as SSMS default).
// To use a SQL login instead, fill in both (see database/optional_sql_login.sql).
define('DB_USER', '');
define('DB_PASS', '');

define('BASE_URL', '/sports-app'); // folder name inside htdocs

date_default_timezone_set('Asia/Manila');

// One shared PDO connection (PDO_SQLSRV). Prepared statements everywhere = no SQL injection.
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // TrustServerCertificate=1 lets ODBC Driver 18 connect to a local SQL Server without a CA certificate.
        $dsn = 'sqlsrv:Server=' . DB_SERVER . ';Database=' . DB_NAME . ';TrustServerCertificate=1;LoginTimeout=5';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = DB_USER === ''
            ? new PDO($dsn, null, null, $options)          // Windows Authentication
            : new PDO($dsn, DB_USER, DB_PASS, $options);   // SQL Server Authentication
    }
    return $pdo;
}
