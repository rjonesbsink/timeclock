<?php

require_once 'lib/session.php';
start_secure_session();

include 'config.inc.php';
include 'header_bootstrap.php';

if (!isset($_GET['printer_friendly'])) {
    if (isset($_SESSION['valid_user'])) {
        $set_logout = "1";
    }

    include 'topmain_bootstrap.php';
    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    include 'leftmain.php';
}

echo "<title>$title</title>\n";
$current_page = "timeclock.php";

if (!isset($_GET['printer_friendly'])) {
    echo "    <div class=\"col-md-9\">\n";
}

// code to allow sorting by Name, In/Out, Date, Notes //

// Note: $sortcolumn and $sortdirection are used as raw SQL identifiers below
// (order by `$sortcolumn` $sortdirection), so they must be validated against
// a whitelist rather than escaped -- placeholders can't bind identifiers.
$valid_sortcolumns = array('empfullname', 'displayname', 'fullname', 'inout', 'tstamp', 'office', 'groups', 'notes');

if ($show_display_name == "yes") {
    $sortcolumn = "displayname";
} else {
    $sortcolumn = "fullname";
}
if (isset($_GET['sortcolumn']) && in_array($_GET['sortcolumn'], $valid_sortcolumns, true)) {
    $sortcolumn = $_GET['sortcolumn'];
}

$sortdirection = "asc";
if (isset($_GET['sortdirection']) && $_GET['sortdirection'] === "desc") {
    $sortdirection = "desc";
}

if ($sortdirection == "asc") {
    $sortnewdirection = "desc";
} else {
    $sortnewdirection = "asc";
}

// determine what users, office, and/or group will be displayed on main page //

if (($display_current_users == "yes") && ($display_office == "all") && ($display_group == "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and ((" . $db_prefix . "info.timestamp < '" . $a . "') and
              (" . $db_prefix . "info.timestamp >= '" . $b . "')) and " . $db_prefix . "employees.disabled <> '1' and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "yes") && ($display_office != "all") && ($display_group == "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and " . $db_prefix . "employees.office = '" . $display_office . "'
              and ((" . $db_prefix . "info.timestamp < '" . $a . "') and (" . $db_prefix . "info.timestamp >= '" . $b . "'))
              and " . $db_prefix . "employees.disabled <> '1' and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "yes") && ($display_office == "all") && ($display_group != "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and " . $db_prefix . "employees.`groups` = '" . $display_group . "'
              and ((" . $db_prefix . "info.timestamp < '" . $a . "') and (" . $db_prefix . "info.timestamp >= '" . $b . "'))
              and " . $db_prefix . "employees.disabled <> '1' and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "yes") && ($display_office != "all") && ($display_group != "all")) {
    $current_users_date = strtotime(date($datefmt));
    $calc = 86400;
    $a = $current_users_date + $calc - @$tzo;
    $b = $current_users_date - @$tzo;

    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and " . $db_prefix . "employees.office = '" . $display_office . "'
              and " . $db_prefix . "employees.`groups` = '" . $display_group . "' and ((" . $db_prefix . "info.timestamp < '" . $a . "')
              and (" . $db_prefix . "info.timestamp >= '" . $b . "')) and " . $db_prefix . "employees.disabled <> '1'
              and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office == "all") && ($display_group == "all")) {
    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and " . $db_prefix . "employees.disabled <> '1'
              and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office != "all") && ($display_group == "all")) {
    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and " . $db_prefix . "employees.office = '" . $display_office . "'
              and " . $db_prefix . "employees.disabled <> '1' and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office == "all") && ($display_group != "all")) {
    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and " . $db_prefix . "employees.`groups` = '" . $display_group . "'
              and " . $db_prefix . "employees.disabled <> '1' and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
} elseif (($display_current_users == "no") && ($display_office != "all") && ($display_group != "all")) {
    $query = "select " . $db_prefix . "info.*, " . $db_prefix . "employees.*, " . $db_prefix . "punchlist.*
              from " . $db_prefix . "info, " . $db_prefix . "employees, " . $db_prefix . "punchlist
              where " . $db_prefix . "info.timestamp = " . $db_prefix . "employees.tstamp and " . $db_prefix . "info.fullname = " . $db_prefix . "employees.empfullname
              and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems and " . $db_prefix . "employees.office = '" . $display_office . "'
              and " . $db_prefix . "employees.`groups` = '" . $display_group . "' and " . $db_prefix . "employees.disabled <> '1'
              and " . $db_prefix . "employees.empfullname <> 'admin'
              order by `$sortcolumn` $sortdirection";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
}

$time = time();
$tclock_hour = gmdate('H', $time);
$tclock_min = gmdate('i', $time);
$tclock_sec = gmdate('s', $time);
$tclock_month = gmdate('m', $time);
$tclock_day = gmdate('d', $time);
$tclock_year = gmdate('Y', $time);
$tclock_stamp = mktime($tclock_hour, $tclock_min, $tclock_sec, $tclock_month, $tclock_day, $tclock_year);

$tclock_stamp = $tclock_stamp + @$tzo;
$tclock_time = date($timefmt, $tclock_stamp);
$tclock_date = date($datefmt, $tclock_stamp);
$report_name = "Current Status Report";

$report_line_class = isset($_GET['printer_friendly']) ? "" : " display_hide";
echo "<div class=\"small$report_line_class\">$report_name &nbsp;----&gt;&nbsp; As of: $tclock_time, $tclock_date</div>\n";

include 'display.php';

if (!isset($_GET['printer_friendly'])) {
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once 'footer_bootstrap.php';
} else {
    // printer_friendly mode skips the shared footer (same as the original
    // header.php/footer.php pair), but still needs to close out the document.
    echo "</body>\n";
    echo "</html>\n";
}
