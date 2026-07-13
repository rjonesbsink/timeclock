<?php

/**
 * Punchclock functions.
 */

require_once 'config.inc.php';
require_once "$TIMECLOCK_PATH/functions.php";

const WHERE_EMPFULLNAME = "empfullname = ?";

////////////////////////////////////////
function mysqli_result($res, $row = 0, $col = 0)
{
    $numrows = mysqli_num_rows($res);
    if ($numrows && $row <= ($numrows - 1) && $row >= 0) {
        mysqli_data_seek($res, $row);
        $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
        if (isset($resrow[$col])) {
            return $resrow[$col];
        }
    }
    return false;
}

////////////////////////////////////////
function make_id($empfullname)
{
    // Make an DOM ID string from the employee id
    // Add emp_ prefix and change spaces into underlines.
    return 'emp_' . str_replace(' ', '_', $empfullname);
}

function unmake_id($id)
{
    return str_replace('_', ' ', preg_replace('/^emp_/', '', $id));
}


////////////////////////////////////////
function lookup_employee($empfullname)
{
    // Return valid empfullname or null
    $name = null;
    $result = tc_select("empfullname", "employees", WHERE_EMPFULLNAME, $empfullname);
    if (!$result || mysqli_num_rows($result) == 0) {
        // Check if displayname was entered.
        $result = tc_select("empfullname", "employees", "lower(displayname) = ?", strtolower($empfullname))
        or trigger_error('lookup_employee: no result: ' . mysqli_error($GLOBALS["___mysqli_ston"]), E_USER_WARNING);
    }
    if ($result && mysqli_num_rows($result) == 1) {
        $name = mysqli_result($result, 0, 0);
    }

    return $name;
}

////////////////////////////////////////
function get_employee_name($empfullname)
{
    $result = tc_select("displayname", "employees", WHERE_EMPFULLNAME, $empfullname);
    if (!$result) {
        trigger_error('get_employee_name: no result: ' . mysqli_error($GLOBALS["___mysqli_ston"]), E_USER_WARNING);

        return false;
    }
    $name = mysqli_result($result, 0, 0);

    return $name;
}

////////////////////////////////////////
function get_employee_password($empfullname)
{
    $result = tc_select("employee_passwd", "employees", WHERE_EMPFULLNAME, $empfullname);
    if (!$result) {
        trigger_error('get_employee_password: no result: ' . mysqli_error($GLOBALS["___mysqli_ston"]), E_USER_WARNING);

        return false;
    }
    $password = mysqli_result($result, 0, 0);

    return $password;
}

////////////////////////////////////////
function is_valid_password($empfullname, $password)
{
    global $use_passwd;
    $employee_passwd = get_employee_password($empfullname);
    if (!$use_passwd) {
        return $password == $employee_passwd;
    }

    $is_valid = tc_verify_password($password, $employee_passwd);
    if ($is_valid) {
        tc_maybe_upgrade_password($empfullname, $password, $employee_passwd);
    }

    return $is_valid;
}

////////////////////////////////////////
function save_employee_password($empfullname, $new_password)
{
    $password = tc_hash_password($new_password);
    tc_update_strings("employees", array("employee_passwd" => $password), WHERE_EMPFULLNAME, $empfullname);

    return true;
}

////////////////////////////////////////
function get_employee_status($empfullname)
{
    // Get employee's current punch-in/out status and time.
    // Return array of in/out(1/0), punch code, timestamp, and notes.
    global $db_prefix;
    $query = <<<End_Of_SQL
select {$db_prefix}employees.*, {$db_prefix}info.*, {$db_prefix}punchlist.*
  from {$db_prefix}employees
  left join {$db_prefix}info on {$db_prefix}info.fullname = {$db_prefix}employees.empfullname
        and {$db_prefix}info.timestamp = {$db_prefix}employees.tstamp
  left join {$db_prefix}punchlist on {$db_prefix}punchlist.punchitems = {$db_prefix}info.`inout`
 where {$db_prefix}employees.disabled <> '1'
   and employees.empfullname = ?
End_Of_SQL;
    $result = tc_query($query, $empfullname);
    if (!$result) {
        trigger_error('get_employee_status: no result: ' . mysqli_error($GLOBALS["___mysqli_ston"]), E_USER_WARNING);

        return false;
    }
    $row = mysqli_fetch_assoc($result);

    return array($row['in_or_out'], $row['color'], $row['inout'], $row['timestamp'], $row['notes']);
}

////////////////////////////////////////
function compute_work_hours($start_time, $end_time, $week_hours, $day_hours_by_date = array())
{
    // Compute work and overtime hours between two times, applying the
    // greater-of daily/weekly overtime rule to each calendar day the
    // segment touches. A segment spanning midnight (an overnight shift) is
    // split so each day's hours are checked against $overtime_daily_limit
    // independently, rather than lumping the whole segment under one day.
    global $one_day;
    $hours = 0;
    $overtime = 0;

    $day_start = $start_time;
    while ($day_start < $end_time) {
        $date = date('Ymd', $day_start);
        // Half-open [day_start, day_end) so consecutive days tile exactly at
        // midnight with no lost or double-counted minute, unlike subtracting
        // 1 second from the boundary (which rounds away a whole minute once
        // compute_hours() rounds down to full minutes).
        $day_end = min($end_time, day_timestamp($day_start) + $one_day);

        $day_hours = compute_hours($day_start, $day_end);
        $day_hours_before = $day_hours_by_date[$date] ?? 0;

        $day_overtime = compute_overtime_hours($day_hours, $week_hours, $day_hours_before);

        $hours += $day_hours - $day_overtime;
        $overtime += $day_overtime;
        $week_hours += $day_hours;
        $day_hours_by_date[$date] = $day_hours_before + $day_hours;

        $day_start = $day_end;
    }

    return array($hours, $overtime, $day_hours_by_date);
}

function compute_hours($start_time, $end_time)
{
    // Compute number of hours between start and end time.
    $start_time -= $start_time % 60; // round down to full minute
    $end_time -= $end_time % 60; // round down to full minute
    return (($end_time - $start_time) / 60) / 60;
}

function compute_overtime_hours($hours, $week_hours, $day_hours = null)
{
    // Compute the amount of overtime for $hours, as the greater of however much
    // pushes the week's cumulative total past $overtime_week_limit and however
    // much pushes the day's cumulative total (if known) past $overtime_daily_limit.
    // $week_hours/$day_hours are the running sums from before $hours is included.
    // Some jurisdictions require both a daily and a weekly overtime threshold, and
    // an employee is owed whichever of the two credits more overtime.
    global $overtime_week_limit, $overtime_daily_limit;
    $overtime_daily_limit = $overtime_daily_limit ?? 0;

    $weekly_overtime = 0;
    if (($overtime_week_limit > 0) && (($week_hours + $hours) > $overtime_week_limit)) {
        $overlimit = ($week_hours + $hours) - $overtime_week_limit;
        $weekly_overtime = $overlimit < $hours ? $overlimit : $hours;
    }

    $daily_overtime = 0;
    if (($day_hours !== null) && ($overtime_daily_limit > 0) && (($day_hours + $hours) > $overtime_daily_limit)) {
        $overlimit = ($day_hours + $hours) - $overtime_daily_limit;
        $daily_overtime = $overlimit < $hours ? $overlimit : $hours;
    }

    return max($weekly_overtime, $daily_overtime);
}

function compute_day_hours($date, $start_time, $end_time)
{
    // Compute number of hours that fall within the given date.
    global $one_day;
    $start_date = date('Ymd', $start_time);
    $end_date = date('Ymd', $end_time);
    if ($start_date == $date && $end_date == $date) {
        return compute_hours($start_time, $end_time);
    }
    if ($start_date == $date) {
        $end_time = day_timestamp($start_time + $one_day) - 1;

        return compute_hours($start_time, $end_time);
    }
    if ($end_date == $date) {
        $start_time = day_timestamp($end_time);

        return compute_hours($start_time, $end_time);
    }

    return 0;
}

function hrs_min($hours)
{
    // Return string of hours:minutes from given decimal hours. Callers
    // sometimes pass a null (e.g. a row with no computed hours yet), so
    // coerce to a number first rather than letting floor() warn on null.
    $hours = (float) $hours;
    // Round minutes slightly to accommodate numbers like 25.99999998
    return sprintf("%02d:%02d", floor($hours), floor((($hours - floor($hours)) * 60) + .1));
}

////////////////////////////////////////
function work_week_begin($local_timestamp = null)
{
    // Return local timestamp of the beginning of the work week.
    global $begin_week_day, $one_day;
    if ($local_timestamp == null) {
        $local_timestamp = time() - server_timezone_offset() + timezone_offset();
    }
    $local_daystamp = day_timestamp($local_timestamp);
    $local_day_of_week = date('w', $local_daystamp);
    $ndays = $local_day_of_week - $begin_week_day;
    if ($ndays < 0) {
        $ndays += 7;
    }

    return $local_daystamp - ($ndays * $one_day);
}

////////////////////////////////////////
function utm_timestamp($local_timestamp = null)
{
    // UTM timestamp for time, default is current local time.
    if ($local_timestamp == null) {
        return time() - server_timezone_offset();
    }

    return $local_timestamp - timezone_offset();
}

function local_timestamp($utm_timestamp = null)
{
    // Local timestamp for time, default is current time.
    if ($utm_timestamp == null) {
        $utm_timestamp = time() - server_timezone_offset();
    }

    return $utm_timestamp + timezone_offset();
}

function day_timestamp($local_timestamp = null)
{
    // Local timestamp for the beginning of the day, default is current local time.
    if ($local_timestamp == null) {
        $local_timestamp = time() - server_timezone_offset() + timezone_offset();
    }
    $month = date('m', $local_timestamp);
    $day = date('d', $local_timestamp);
    $year = date('Y', $local_timestamp);

    return mktime(0, 0, 0, $month, $day, $year);
}

function make_timestamp($date_str)
{
    // Make local timestamp from date string of mm/dd/yyyy or dd/mm/yyyy.
    global $calendar_style;
    $arr = preg_split('/\W/', $date_str);
    $ts = $calendar_style == "euro" ? mktime(0, 0, 0, $arr[1], $arr[0], $arr[2]) : mktime(0, 0, 0, $arr[0], $arr[1], $arr[2]);

    return $ts;
}

function server_timezone_offset()
{
    // Get time zone offset of this server.
    global $use_server_tz;

    if ($use_server_tz == "yes") {
        $tzo = date('Z');
    } else {
        $tzo = 0;
    }

    return $tzo;
}

function timezone_offset()
{
    // Get time zone offset (from timeclock header.php)
    global $use_client_tz, $use_server_tz;

    if ($use_client_tz == "yes") {
        if (isset($_COOKIE['tzoffset'])) {
            $tzo = $_COOKIE['tzoffset'];
            settype($tzo, "integer");
            $tzo = $tzo * 60;
        }
    } elseif ($use_server_tz == "yes") {
        $tzo = date('Z');
    } else {
        $tzo = 0;
    }

    return $tzo;
}

////////////////////////////////////////
function exit_next($url)
{
    // Go to next page
    header("Location: $url");
    exit;

    // Following causes browser to "blank screen" between pages.
    print <<<End_Of_HTML
<html><head><meta http-equiv="Refresh" CONTENT="0; URL=$url"></head><body></body></html>
End_Of_HTML;
    exit;
}

////////////////////////////////////////
function session_stop()
{
    // Counterpart to php session_start(). Adapted from php.net.
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
        unset($_COOKIE[session_name()]);
    }
    $_SESSION = array();
    @session_destroy();
}

////////////////////////////////////////
function bool($str = null)
{
    // true/false or yes/no
    if ($str && preg_match('/^\s*(no|false|0+)\s*$/i', $str)) {
        return false;
    }
    if ($str) {
        return true;
    }

    return false;
}

////////////////////////////////////////
function turn_off_magic_quotes()
{
    // No-op: "magic quotes" was removed from PHP itself in PHP 5.4.
}

////////////////////////////////////////
function msg($msg)
{
    return <<< EOF
<div class="message">
$msg
</div>
EOF;
}

////////////////////////////////////////
function error_msg($msg)
{
    return <<< EOF
<div class="error">
$msg
</div>
EOF;
}
