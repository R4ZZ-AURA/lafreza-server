<?php
require_once '../shared/auth.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Access denied');
}

require_once '../shared/connection.php';

global $dbHost, $dbUser, $dbPass, $dbName;

$date = date('Y-m-d_H-i-s');
$filename = "backup_{$dbName}_{$date}.sql";

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename={$filename}");

// ✅ Correct mysqldump path
$mysqldumpPath = "C:/wamp64/bin/mysql/mysql8.2.0/bin/mysqldump.exe"; // or use double backslashes

// ✅ Use the actual path in the command
$command = "\"{$mysqldumpPath}\" --user={$dbUser} --password={$dbPass} --host={$dbHost} {$dbName}";

// ✅ Output the SQL dump directly to browser
passthru($command, $resultCode);

// ✅ Error handling
if ($resultCode !== 0) {
    echo "Error: mysqldump command failed.";
}
exit;
