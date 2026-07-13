<?php

/**
 * Edit an employee's recurring weekly schedule (expected clock-in/out times
 * per day of week), used by the exception report to flag absences and
 * late/early punches.
 */

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header.php';
echo "<title>$title - Edit Schedule</title>\n";

// $_SERVER['PHP_SELF'] reflects attacker-controlled path info verbatim
// (e.g. /admin/scheduleedit.php/"><script>...), so it must be escaped
// before landing in an HTML attribute below.
$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const WHERE_EMPFULLNAME = "empfullname = ?";
const FOOTER_PHP = '../footer.php';
const TABLE_OPEN_HEADING = "            <table align=center class=table_border width=80% border=0 cellpadding=3 cellspacing=0>\n";
const TABLE_OPEN_SPACER = "            <table align=center width=80% border=0 cellpadding=0 cellspacing=3>\n";
const SCHEDULE_DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

// Render the 7 day-of-week rows. $values is [day_of_week => ['checked' =>
// bool, 'start' => string, 'end' => string]], already-htmlentities-escaped.
function render_schedule_rows($values)
{
    $html = '';
    foreach (SCHEDULE_DAY_NAMES as $day_of_week => $day_name) {
        $checked = $values[$day_of_week]['checked'] ? 'checked' : '';
        $start = $values[$day_of_week]['start'];
        $end = $values[$day_of_week]['end'];
        $html .= "              <tr><td class=table_rows height=25 width=20% style='padding-left:32px;' nowrap>$day_name:</td>"
            . "<td width=15% style='padding-left:20px;'><input type='checkbox' name='scheduled_$day_of_week' value='1' $checked>&nbsp;Scheduled</td>"
            . "<td width=32% style='padding-left:20px;'>Start:&nbsp;<input type='time' name='start_$day_of_week' value=\"$start\"></td>"
            . "<td width=32% style='padding-left:20px;'>End:&nbsp;<input type='time' name='end_$day_of_week' value=\"$end\"></td></tr>\n";
    }
    return $html;
}

function default_row_values($schedule)
{
    $values = array();
    foreach (SCHEDULE_DAY_NAMES as $day_of_week => $day_name) {
        $day = $schedule[$day_of_week] ?? null;
        $values[$day_of_week] = array(
            'checked' => $day !== null,
            'start' => $day !== null ? htmlentities(substr($day['start_time'], 0, 5)) : '',
            'end' => $day !== null ? htmlentities(substr($day['end_time'], 0, 5)) : '',
        );
    }
    return $values;
}

if ($request == 'GET') {
    if (!isset($_GET['username'])) {
        echo "<table width=100% border=0 cellpadding=7 cellspacing=1>\n";
        echo "  <tr class=right_main_text><td height=10 align=center valign=top scope=row class=title_underline>PHP Timeclock Error!</td></tr>\n";
        echo "  <tr class=right_main_text>\n";
        echo "    <td align=center valign=top scope=row>\n";
        echo "      <table width=300 border=0 cellpadding=5 cellspacing=0>\n";
        echo "        <tr class=right_main_text><td align=center>How did you get here?</td></tr>\n";
        echo "        <tr class=right_main_text><td align=center>Go back to the <a class=admin_headings href='useradmin.php'>User Summary</a>
            page to edit a schedule.</td></tr>\n";
        echo "      </table><br /></td></tr></table>\n";
        exit;
    }

    $get_user = get_string('username');
    $username = tc_select_value("empfullname", "employees", WHERE_EMPFULLNAME, $get_user);
    if ($username === null) {
        echo "username is not defined for this user.\n";
        exit;
    }

    $values = default_row_values(get_employee_schedule($username));

    echo admin_schedule_left_nav($username);
    echo "    <td align=left class=right_main scope=col>\n";
    echo "      <table width=100% height=100% border=0 cellpadding=10 cellspacing=1>\n";
    echo "        <tr class=right_main_text>\n";
    echo "          <td valign=top>\n";
    echo "            <br />\n";
    echo "            <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "            <input type='hidden' name='post_username' value=\"" . htmlentities($username) . "\">\n";
    echo TABLE_OPEN_HEADING;
    echo "              <tr><th class=rightside_heading nowrap halign=left colspan=4><img src='../images/icons/clock.png' />&nbsp;&nbsp;&nbsp;
                  Schedule: " . htmlentities($username) . "</th></tr>\n";
    echo "              <tr><td height=15></td></tr>\n";
    echo render_schedule_rows($values);
    echo "              <tr><td height=15></td></tr>\n";
    echo "            </table>\n";
    echo TABLE_OPEN_SPACER;
    echo "              <tr><td height=40></td></tr>\n";
    echo "            </table>\n";
    echo TABLE_OPEN_SPACER;
    echo "              <tr><td width=30><input type='image' name='submit' value='Save Schedule' src='../images/buttons/next_button.png'></td>
                  <td><a href='useradmin.php'><img src='../images/buttons/cancel_button.png' border='0'></td></tr></table></form></td></tr>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $post_username = post_string('post_username');
    $username = tc_select_value("empfullname", "employees", WHERE_EMPFULLNAME, $post_username);
    if ($username === null) {
        echo "username is not defined for this user.\n";
        exit;
    }

    $errors = array();
    $days = array();
    $values = array();
    foreach (SCHEDULE_DAY_NAMES as $day_of_week => $day_name) {
        $scheduled = isset($_POST["scheduled_$day_of_week"]);
        $start = post_string("start_$day_of_week");
        $end = post_string("end_$day_of_week");

        if ($scheduled) {
            if (!is_valid_time_of_day($start) || !is_valid_time_of_day($end)) {
                $errors[] = "Enter a valid start and end time for $day_name.";
            } else {
                $days[$day_of_week] = array('start_time' => $start, 'end_time' => $end);
            }
        }

        $values[$day_of_week] = array(
            'checked' => $scheduled,
            'start' => htmlentities($start),
            'end' => htmlentities($end),
        );
    }

    echo admin_schedule_left_nav($username);
    echo "    <td align=left class=right_main scope=col>\n";
    echo "      <table width=100% height=100% border=0 cellpadding=10 cellspacing=1>\n";
    echo "        <tr class=right_main_text>\n";
    echo "          <td valign=top>\n";
    echo "            <br />\n";

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "            <table align=center class=table_border width=80% border=0 cellpadding=0 cellspacing=3>\n";
            echo "              <tr><td class=table_rows width=20 align=center><img src='../images/icons/cancel.png' /></td>
                  <td class=table_rows_red>$error</td></tr>\n";
            echo "            </table>\n";
        }

        echo "            <br />\n";
        echo "            <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "            <input type='hidden' name='post_username' value=\"" . htmlentities($username) . "\">\n";
        echo TABLE_OPEN_HEADING;
        echo "              <tr><th class=rightside_heading nowrap halign=left colspan=4><img src='../images/icons/clock.png' />&nbsp;&nbsp;&nbsp;
                    Schedule: " . htmlentities($username) . "</th></tr>\n";
        echo "              <tr><td height=15></td></tr>\n";
        echo render_schedule_rows($values);
        echo "              <tr><td height=15></td></tr>\n";
        echo "            </table>\n";
        echo TABLE_OPEN_SPACER;
        echo "              <tr><td height=40></td></tr>\n";
        echo "            </table>\n";
        echo TABLE_OPEN_SPACER;
        echo "              <tr><td width=30><input type='image' name='submit' value='Save Schedule' src='../images/buttons/next_button.png'></td>
                    <td><a href='useradmin.php'><img src='../images/buttons/cancel_button.png' border='0'></td></tr></table></form></td></tr>\n";
        include_once FOOTER_PHP;
        exit;
    }

    set_employee_schedule($username, $days);

    echo "            <table align=center class=table_border width=80% border=0 cellpadding=0 cellspacing=3>\n";
    echo "              <tr><td class=table_rows width=20 align=center><img src='../images/icons/accept.png' /></td>
              <td class=table_rows_green>&nbsp;Schedule saved successfully.</td></tr>\n";
    echo "            </table>\n";
    echo "            <br />\n";
    echo TABLE_OPEN_HEADING;
    echo "              <tr><th class=rightside_heading nowrap halign=left colspan=4><img src='../images/icons/clock.png' />&nbsp;&nbsp;&nbsp;
                Schedule: " . htmlentities($username) . "</th></tr>\n";
    echo "              <tr><td height=15></td></tr>\n";
    echo render_schedule_rows($values);
    echo "              <tr><td height=15></td></tr>\n";
    echo "            </table>\n";
    echo TABLE_OPEN_SPACER;
    echo "              <tr><td height=20 align=left>&nbsp;</td></tr>\n";
    echo "              <tr><td><a href='useradmin.php'><img src='../images/buttons/done_button.png' border='0'></a></td></tr></table>\n";
    include_once FOOTER_PHP;
    exit;
}
