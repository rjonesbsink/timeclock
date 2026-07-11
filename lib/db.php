<?php

/*
 * Shared mysqli connection bootstrap. Include this after config.inc.php so
 * $db_hostname/$db_username/$db_password/$db_name are already in scope.
 * Sets $db and $GLOBALS["___mysqli_ston"] for the rest of the app to use.
 *
 * If config.inc.php doesn't exist at all, $db_hostname is never set, since
 * every caller's own include/require of it silently no-ops on a missing
 * file. Send the browser to the setup wizard instead of trying (and
 * failing) to connect with null credentials.
 */

if (!isset($db_hostname)) {
    $urlBase = '';
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    $appRoot = dirname(__DIR__);
    if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        $urlBase = substr($appRoot, strlen($docRoot));
    }
    $setupUrl = $urlBase . '/setup.php';

    if (headers_sent()) {
        // A caller printed something (e.g. a literal <html> before its own
        // <?php block) before reaching this point, so the HTTP redirect
        // below can't be sent -- this is the only path to the setup wizard
        // on a fresh install, so fall back to a client-side redirect rather
        // than leaving the visitor stuck on a broken page.
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($setupUrl) . '">';
        echo '<p>PHP Timeclock needs to be configured. <a href="' . htmlspecialchars($setupUrl) . '">Continue to setup</a>.</p>';
    } else {
        header('Location: ' . $setupUrl);
    }
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = mysqli_connect($db_hostname, $db_username, $db_password, $db_name);
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo "Error: Could not connect to the database. Please try again later.";
    exit;
}

$GLOBALS["___mysqli_ston"] = $db;
