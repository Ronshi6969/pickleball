<?php
//variables for the database details
$serverName = "LAPTOP-9M40AKII\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true,
    "TrustServerCertificate" => true
];

define('DB_SERVER', $serverName);
define('DB_NAME', $connectionOptions['Database']);
define('DB_USER', $connectionOptions['uid']);
define('DB_PASS', $connectionOptions['PWD']);

define('BASE_URL', '/pickleball/sports-app'); // folder name inside htdocs

date_default_timezone_set('Asia/Manila');

class SqlsrvException extends RuntimeException
{
    public array $errors;
    public function __construct(string $message, array $errors = [])
    {
        $this->errors = $errors;
        $code = (int)($errors[0]['code'] ?? 0);
        parent::__construct($message, $code);
    }
}

//connection to the database using sqlsrv_connect (same format as Aguirri lab)
function db()
{
    static $conn = null;
    if ($conn === null) {
        global $serverName, $connectionOptions;
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn === false) {
            throw new SqlsrvException('Could not connect to SQL Server.', sqlsrv_errors() ?: []);
        }
    }
    return $conn;
}
