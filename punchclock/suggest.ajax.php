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
require_once "$TIMECLOCK_PATH/functions.php";

// Parse arguments.
// Guard against ?q[]=x: string-concatenating an array only warns rather
// than crashing, but treating it the same as "not submitted" is clearer
// than silently searching for the literal text "Array%".
$search = get_string('q', null);
if (!$search) {
    exit;
}

// Connect to db.
require_once "$TIMECLOCK_PATH/lib/db.php";

// Search for employee names beginning with query
$result = tc_select("displayname", "employees", "displayname like ?", $search . '%');
if (!$result) {
    trigger_error('suggest.ajax.php: error: ' . mysqli_error($GLOBALS["___mysqli_ston"]), E_USER_WARNING);
    die();
}

while ($row = mysqli_fetch_assoc($result)) {
    print $row['displayname'] . "\n";
}
