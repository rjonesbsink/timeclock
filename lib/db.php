<?php

/*
 * Shared mysqli connection bootstrap. Include this after config.inc.php so
 * $db_hostname/$db_username/$db_password/$db_name are already in scope.
 * Sets $db and $GLOBALS["___mysqli_ston"] for the rest of the app to use.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = mysqli_connect($db_hostname, $db_username, $db_password, $db_name);
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo "Error: Could not connect to the database. Please try again later.";
    exit;
}

$GLOBALS["___mysqli_ston"] = $db;
