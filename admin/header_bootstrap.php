<?php

/**
 * Bootstrap-based drop-in replacement for admin/header.php. Same
 * connectivity/IP/timezone checks as admin/header.php -- only the HTML
 * output differs. Used by admin pages migrated to the new Bootstrap layout
 * (see issue #40); admin/header.php itself is untouched and still serves
 * every admin page not yet migrated.
 */

include_once '../functions.php';

ob_start();
echo "<!doctype html>\n<html lang=\"en\">\n";

// grab the connecting ip address. //

$connecting_ip = get_ipaddress();

if (empty($connecting_ip)) {
    return false;
}

// determine if connecting ip address is allowed to connect to PHP Timeclock //

if ($restrict_ips == "yes") {
    for ($x = 0; $x < count($allowed_networks); $x++) {
        $is_allowed = ip_range($allowed_networks[$x], $connecting_ip);
        if (!empty($is_allowed)) {
            $allowed = true;
        }
    }
    if (!isset($allowed)) {
        echo "You are not authorized to view this page.";
        exit;
    }
}

// connect to db and check for correct db version //

require_once '../lib/db.php';

$table = "dbversion";
// $db_prefix comes from config.inc.php, not user input -- same trusted-config
// concatenation pattern used throughout functions.php for table names, which
// can't be bound as query parameters.
$result = mysqli_query($GLOBALS["___mysqli_ston"], "SHOW TABLES LIKE '" . $db_prefix . $table . "'"); // NOSONAR
@$rows = mysqli_num_rows($result);

if ($rows == "1") {
    $dbexists = "1";
} else {
    $dbexists = "0";
}

// Same trusted-config table-name concatenation as above.
$db_version_result = mysqli_query($GLOBALS["___mysqli_ston"], "select * from " . $db_prefix . "dbversion"); // NOSONAR
while (@$row = mysqli_fetch_array($db_version_result)) {
    @$my_dbversion = "" . $row["dbversion"] . "";
}

if (($use_client_tz == "yes") && ($use_server_tz == "yes")) {
    echo 'Please reconfigure your config.inc.php file, you cannot have both $use_client_tz AND $use_server_tz set to \'yes\'';
    exit;
}

echo "<head>\n";
echo "<meta charset=\"utf-8\">\n";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";

if ($use_client_tz == "yes" && !isset($_COOKIE['tzoffset'])) {
    include_once '../tzoffset.php';
    echo "<meta http-equiv='refresh' content='0;URL=index.php'>\n";
}

echo "<link rel='stylesheet' href='../css/bootstrap.min.css'>\n";
echo "<link rel='stylesheet' href='../css/bootstrap-theme.css'>\n";

// set refresh rate for each page //

if ($refresh == "none") {
    echo "</head>\n";
} else {
    echo "<meta http-equiv='refresh' content=\"$refresh;URL=index.php\">\n";
    echo "</head>\n";
}

setTimeZone();

?>
<body class="bg-light">
