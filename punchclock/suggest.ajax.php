<?php

/**
 * Supply suggestions for employee names.
 *
 * This is an AJAX form and it returns a simple list of text.
 */

session_start();
require_once '../lib/auth.php';
require_application_context();

require_once 'config.inc.php';

// Parse arguments.
$search = isset($_GET['q']) ? $_GET['q'] : null;
if (!$search) {
    exit;
}

// Connect to db.
require_once "$TIMECLOCK_PATH/lib/db.php";

// Search for employee names beginning with query
require_once "$TIMECLOCK_PATH/functions.php";
$result = tc_select("displayname", "employees", "displayname like ?", $search . '%');
if (!$result) {
    trigger_error('suggest.ajax.php: error: ' . mysqli_error($GLOBALS["___mysqli_ston"]), E_USER_WARNING);
    die();
}

while ($row = mysqli_fetch_assoc($result)) {
    print $row['displayname'] . "\n";
}
