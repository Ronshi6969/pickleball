<?php
// Self-test for the SQL Server setup (data for setup_check.html). Local access only.
// When everything is green, DELETE setup_check.html and this file.
header('Content-Type: application/json; charset=utf-8');
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit(json_encode(['error' => 'Local access only.']));
}
require_once __DIR__ . '/../includes/functions.php';

$checks = [];
$add = function (string $label, bool $ok, string $detail, string $hint = '') use (&$checks) {
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail, 'hint' => $hint];
};

$add('PHP version', true, PHP_VERSION . ' (' . (PHP_INT_SIZE * 8) . '-bit, ' . (ZEND_THREAD_SAFE ? 'Thread Safe' : 'Non Thread Safe') . ')');
$hasDriver = extension_loaded('sqlsrv');
$add('sqlsrv extension', $hasDriver, $hasDriver ? 'loaded' : 'NOT loaded',
     'Copy the matching php_sqlsrv DLL to C:\\xampp\\php\\ext, add the extension= line to php.ini, then restart Apache.');

if ($hasDriver) {
    try {
        db();
        $add('Connect to SQL Server', true, DB_SERVER . ' / database ' . DB_NAME . ' (' . (DB_USER === '' ? 'Windows Authentication' : 'SQL login ' . DB_USER) . ')');
        $users = (int)db_val('SELECT COUNT(*) FROM users');
        $add('Tables and demo data', $users > 0, $users . ' users found', 'Run database/schema_sqlserver.sql in SSMS.');
        $ver = (string)db_val('SELECT @@VERSION');
        $add('SQL Server version', true, strtok($ver, "\n"));
    } catch (Throwable $ex) {
        $msg = $ex->getMessage();
        if ($ex instanceof SqlsrvException && !empty($ex->errors[0]['message'])) {
            $msg = $ex->errors[0]['message'];
        }
        $hint = 'Check $serverName and $connectionOptions in config/config.php.';
        if (stripos($msg, 'Login failed') !== false)                    { $hint = 'Login rejected. For Windows Authentication make sure Apache runs as your Windows user and that user has a login in SQL Server; or set uid/PWD in config/config.php.'; }
        elseif (stripos($msg, 'Cannot open database') !== false)         { $hint = 'The database does not exist yet. Run database/schema_sqlserver.sql in SSMS.'; }
        elseif (stripos($msg, 'network-related') !== false || stripos($msg, 'Named Pipes') !== false || stripos($msg, 'not found or not accessible') !== false) { $hint = 'Server not reachable. Use the same server name as in SSMS (e.g. localhost\\SQLEXPRESS), make sure the SQL Server service is running and "SQL Server Browser" is running for named instances.'; }
        elseif (stripos($msg, 'certificate') !== false)                  { $hint = 'Certificate problem: keep "TrustServerCertificate" => true in config/config.php.'; }
        elseif (stripos($msg, 'Data source name not found') !== false || stripos($msg, 'driver') !== false) { $hint = 'The Microsoft ODBC Driver 17/18 for SQL Server is not installed.'; }
        $add('Connect to SQL Server', false, $msg, $hint);
    }
}
echo json_encode(['checks' => $checks, 'base_url' => BASE_URL], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
