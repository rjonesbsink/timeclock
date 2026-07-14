<?php

/**
 * Schedule exception report: flags absences and late/early punches against
 * each employee's weekly schedule (see admin/scheduleedit.php).
 */

require_once '../lib/session.php';
start_secure_session();

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const ADMIN_TOPMAIN_PHP = '../admin/topmain.php';
const EXCEPTION_LABELS = [
    'absence' => 'Absent',
    'late' => 'Late arrival',
    'early' => 'Left early',
];

include_once '../config.inc.php';
require_once '../functions.php';
require_once '../lib/auth.php';
require_once '../lib/csrf.php';

if (!isset($tzo)) {
    settype($tzo, "integer");
    if (isset($_COOKIE['tzoffset'])) {
        $tzo = $_COOKIE['tzoffset'] * 60;
    } else {
        $tzo = 0;
    }
}

if (reports_login_required()) {
    include_once '../admin/header.php';
    include_once ADMIN_TOPMAIN_PHP;
    echo "<title>$title</title>\n";
    print_login_required_message('../login_reports.php', true);
    exit;
}

echo "<title>$title - Exception Report</title>\n";

// Renders one line of exception detail, e.g. "Late arrival by 25 minutes
// (scheduled 9:00am)" or "Absent (scheduled 9:00am - 5:00pm)".
function format_exception_detail($exception, $schedule_day, $timefmt)
{
    $scheduled_start = date($timefmt, strtotime($schedule_day['start_time']));
    $scheduled_end = date($timefmt, strtotime($schedule_day['end_time']));

    if ($exception['type'] === 'absence') {
        return "Absent (scheduled $scheduled_start - $scheduled_end)";
    }
    if ($exception['type'] === 'late') {
        return "Late by {$exception['minutes']} minutes (scheduled $scheduled_start)";
    }

    return "Left early by {$exception['minutes']} minutes (scheduled until $scheduled_end)";
}

if ($request == 'GET') {
    include_once '../admin/header_date.php';

    if ($use_reports_password == "yes") {
        include_once ADMIN_TOPMAIN_PHP;
    } else {
        include_once 'topmain.php';
    }

    $result = tc_select("empfullname, displayname", "employees", "disabled <> 1 order by empfullname asc");

    echo "<table width=100% height=89% border=0 cellpadding=0 cellspacing=1>\n";
    echo "  <tr valign=top>\n";
    echo "    <td>\n";
    echo "      <table width=100% height=100% border=0 cellpadding=10 cellspacing=1>\n";
    echo "        <tr class=right_main_text>\n";
    echo "          <td valign=top>\n";
    echo "            <br />\n";
    echo "            <table align=center class=table_border width=60% border=0 cellpadding=0 cellspacing=3>\n";
    echo "              <tr><td class=table_rows width=20 align=center><img src='../images/icons/information.png' /></td><td class=table_rows
                  style='color:#3366CC;'>This report compares each employee's punches against their schedule (Administration -> User
                  Summary -> Schedule) and flags absences, late arrivals, and early departures beyond the configured grace period
                  ($schedule_grace_minutes minutes).</td></tr>\n";
    echo "            </table>\n";
    echo "            <br />\n";
    echo "            <form name='form' action='$self' method='post' onsubmit=\"return isFromOrToDate();\">\n";
    echo csrf_field() . "\n";
    echo "            <table align=center class=table_border width=60% border=0 cellpadding=3 cellspacing=0>\n";
    echo "              <tr><th class=rightside_heading nowrap halign=left colspan=3><img src='../images/icons/report.png' />&nbsp;&nbsp;&nbsp;
                  Exception Report</th></tr>\n";
    echo "              <tr><td height=15></td></tr>\n";
    echo "              <input type='hidden' name='date_format' value='$js_datefmt'>\n";
    echo "              <tr><td class=table_rows height=25 width=20% style='padding-left:32px;' nowrap>Employee:</td><td colspan=2 width=80%
                  style='padding-left:20px;'><select name='user_name'>\n";
    echo "                    <option value='All'>All Employees</option>\n";
    echo html_options($result);
    echo "                  </select></td></tr>\n";
    echo "              <tr><td class=table_rows style='padding-left:32px;' width=20% nowrap>From Date: ($tmp_datefmt)</td><td
                  style='color:red;font-family:Tahoma;font-size:10px;padding-left:20px;' width=80% >
                  <input type='text' size='10' maxlength='10' name='from_date' style='color:#27408b'>&nbsp;*&nbsp;&nbsp;
                  <a href=\"#\" onclick=\"form.from_date.value='';cal.select(document.forms['form'].from_date,'from_date_anchor','$js_datefmt');
                  return false;\" name=\"from_date_anchor\" id=\"from_date_anchor\" style='font-size:11px;color:#27408b;'>Pick Date</a></td></tr>\n";
    echo "              <tr><td class=table_rows style='padding-left:32px;' width=20% nowrap>To Date: ($tmp_datefmt)</td><td
                  style='color:red;font-family:Tahoma;font-size:10px;padding-left:20px;' width=80% >
                  <input type='text' size='10' maxlength='10' name='to_date' style='color:#27408b'>&nbsp;*&nbsp;&nbsp;
                  <a href=\"#\" onclick=\"form.to_date.value='';cal.select(document.forms['form'].to_date,'to_date_anchor','$js_datefmt');
                  return false;\" name=\"to_date_anchor\" id=\"to_date_anchor\" style='font-size:11px;color:#27408b;'>Pick Date</a></td></tr>\n";
    echo "              <tr><td class=table_rows align=right colspan=3 style='color:red;font-family:Tahoma;font-size:10px;'>*&nbsp;required&nbsp;</td></tr>\n";
    echo "            </table>\n";
    echo "            <div style=\"position:absolute;visibility:hidden;background-color:#ffffff;layer-background-color:#ffffff;\" id=\"mydiv\"
             height=200>&nbsp;</div>\n";
    echo "            <table align=center width=60% border=0 cellpadding=0 cellspacing=3>\n";
    echo "              <tr><td height=20></td></tr>\n";
    echo "              <tr><td width=30><input type='image' name='submit' value='Run Report' alt='Run Report' src='../images/buttons/next_button.png'></td>
              <td><a href='index.php'><img src='../images/buttons/cancel_button.png' border='0' alt='Cancel'></a></td></tr></table></form></td></tr>\n";
    include_once '../footer.php';
    exit;
}

require_csrf_token();
include_once '../admin/header_date.php';

if ($use_reports_password == "yes") {
    include_once ADMIN_TOPMAIN_PHP;
} else {
    include_once 'topmain.php';
}

$user_name = post_string('user_name');
$from_date = post_string('from_date');
$to_date = post_string('to_date');

$from_local = parse_report_date($from_date, $calendar_style);
$to_local = parse_report_date($to_date, $calendar_style);

echo "<table width=100% height=89% border=0 cellpadding=0 cellspacing=1>\n";
echo "  <tr valign=top>\n";
echo "    <td>\n";
echo "      <table width=100% height=100% border=0 cellpadding=10 cellspacing=1>\n";
echo "        <tr class=right_main_text>\n";
echo "          <td valign=top>\n";
echo "            <br />\n";

if ($from_local === null || $to_local === null) {
    echo "            <table align=center class=table_border width=60% border=0 cellpadding=0 cellspacing=3>\n";
    echo "              <tr><td class=table_rows width=20 align=center><img src='../images/icons/cancel.png' /></td><td class=table_rows_red>
              A valid From Date and To Date are both required.</td></tr>\n";
    echo "            </table>\n";
    include_once '../footer.php';
    exit;
}

if ($from_local > $to_local) {
    echo "            <table align=center class=table_border width=60% border=0 cellpadding=0 cellspacing=3>\n";
    echo "              <tr><td class=table_rows width=20 align=center><img src='../images/icons/cancel.png' /></td><td class=table_rows_red>
              From Date must not be later than To Date.</td></tr>\n";
    echo "            </table>\n";
    include_once '../footer.php';
    exit;
}

$from_timestamp = $from_local - $tzo;
$to_timestamp = $to_local + 86400 - $tzo;

if ($user_name === 'All' || empty($user_name)) {
    $result = tc_select("empfullname, displayname", "employees", "disabled <> 1 order by empfullname asc");
    $employees = [];
    while ($row = mysqli_fetch_array($result)) {
        $employees[] = ["empfullname" => $row["empfullname"], "displayname" => $row["displayname"]];
    }
} else {
    $displayname = tc_select_value("displayname", "employees", "empfullname = ?", $user_name);
    if ($displayname === null) {
        echo "            <table align=center class=table_border width=60% border=0 cellpadding=0 cellspacing=3>\n";
        echo "              <tr><td class=table_rows width=20 align=center><img src='../images/icons/cancel.png' /></td><td class=table_rows_red>
                  Employee was not recognized.</td></tr>\n";
        echo "            </table>\n";
        include_once '../footer.php';
        exit;
    }
    $employees = [["empfullname" => $user_name, "displayname" => $displayname]];
}

echo "<table width=100% align=center class=misc_items border=0 cellpadding=3 cellspacing=0>\n";
echo "  <tr><td width=80% style='font-size:9px;color:#000000;padding-left:10px;'>Exception Report</td><td nowrap
            style='font-size:9px;color:#000000;'>Date Range: " . htmlentities($from_date) . " - " . htmlentities($to_date) . "</td></tr>\n";
echo "</table>\n";

$row_count = 0;
$row_color = $color2;

echo "<table class=misc_items width=100% border=0 cellpadding=2 cellspacing=0>\n";
echo "  <tr><td height=15></td></tr>\n";
echo "  <tr>\n";
echo "    <td nowrap width=25% align=left style='padding-left:10px;font-size:11px;color:#27408b;text-decoration:underline;'>Employee</td>\n";
echo "    <td nowrap width=15% align=left style='padding-left:10px;font-size:11px;color:#27408b;text-decoration:underline;'>Date</td>\n";
echo "    <td nowrap width=15% align=left style='padding-left:10px;font-size:11px;color:#27408b;text-decoration:underline;'>Exception</td>\n";
echo "    <td nowrap width=45% align=left style='padding-left:10px;font-size:11px;color:#27408b;text-decoration:underline;'>Detail</td></tr>\n";

foreach ($employees as $employee) {
    $schedule = get_employee_schedule($employee["empfullname"]);
    $exceptions = get_employee_exceptions($employee["empfullname"], $from_timestamp, $to_timestamp, $tzo, $schedule_grace_minutes);
    $h_displayname = htmlentities(stripslashes($employee["displayname"]));

    foreach ($exceptions as $exception) {
        $date_timestamp = mktime(0, 0, 0, (int) substr($exception['date'], 4, 2), (int) substr($exception['date'], 6, 2), (int) substr($exception['date'], 0, 4));
        $day_of_week = (int) date('w', $date_timestamp);
        $schedule_day = $schedule[$day_of_week];

        $row_color = ($row_count % 2) ? $color1 : $color2;
        $h_date = htmlentities(date($datefmt, $date_timestamp));
        $h_label = htmlentities(EXCEPTION_LABELS[$exception['type']]);
        $h_detail = htmlentities(format_exception_detail($exception, $schedule_day, $timefmt));

        echo "  <tr><td nowrap align=left width=25% style='background-color:$row_color;color:#000000;padding-left:10px;'>$h_displayname</td>\n";
        echo "    <td nowrap align=left width=15% style='background-color:$row_color;color:#000000;padding-left:10px;'>$h_date</td>\n";
        echo "    <td nowrap align=left width=15% style='background-color:$row_color;color:#000000;padding-left:10px;'>$h_label</td>\n";
        echo "    <td align=left width=45% style='background-color:$row_color;color:#000000;padding-left:10px;'>$h_detail</td></tr>\n";
        $row_count++;
    }
}

if ($row_count === 0) {
    echo "  <tr><td colspan=4 align=center style='padding:15px;color:#009900;'>No exceptions found for this date range.</td></tr>\n";
}

echo "</table>\n";
include_once '../footer.php';
exit;
