<?php

function _tc_bind_param($stmt, $params, $types)
{
    if (is_null($params)) {
        $params = array();
    }

    if (!is_array($params)) {
        $params = array($params);
    }

    if (empty($params)) {
        return;
    }

    if (is_null($types)) {
        $types = str_repeat("s", count($params));
    }

    $refs = array();
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $types);
    return call_user_func_array(array($stmt, 'bind_param'), @$refs);
}

function tc_execute($query, $params = array(), $types = null)
{
    if (!($stmt = $GLOBALS["___mysqli_ston"]->prepare($query))) {
        error_log("Failed to prepare $query: " . mysqli_error($GLOBALS["___mysqli_ston"]));
        return false;
    }
    _tc_bind_param($stmt, $params, $types);
    if (!$stmt->execute()) {
        error_log("Failed to execute: " . $stmt->error);
        return false;
    }
    return $stmt->close();
}

function tc_query($query, $params = array(), $types = null)
{
    if (!($stmt = $GLOBALS["___mysqli_ston"]->prepare($query))) {
        error_log("Failed to prepare $query: " . mysqli_error($GLOBALS["___mysqli_ston"]));
        return false;
    }
    _tc_bind_param($stmt, $params, $types);
    if (!$stmt->execute()) {
        error_log("Failed to execute: " . $stmt->error);
        return false;
    }
    return $stmt->get_result();
}

// Backtick-quotes $from when it's a single bare table name, so reserved
// words like `groups` work as table names. Left alone (aside from the
// prefix) when $from is a multi-table FROM/JOIN expression, since those
// can't be quoted as a single identifier and callers already qualify each
// table in them individually.
function tc_qualify_from($from)
{
    global $db_prefix;
    if (preg_match('/^\w+$/', $from)) {
        return "`{$db_prefix}{$from}`";
    }
    return "{$db_prefix}{$from}";
}

function tc_select($what, $from, $where = '1=1', $params = array(), $types = null)
{
    return tc_query("SELECT $what FROM " . tc_qualify_from($from) . " WHERE $where", $params, $types);
}

function tc_select_value($what, $from, $where = '1=1', $params = array(), $types = null)
{
    $result = tc_query("SELECT $what FROM " . tc_qualify_from($from) . " WHERE $where", $params, $types);
    $value = null;
    while ($row = mysqli_fetch_array($result)) {
        $value = $row[0];
    }
    return $value;
}

// True if a row already exists with $name in $nameColumn of $table. Pass
// $extraWhere/$extraParams to scope the uniqueness check further (e.g. a
// group name only needs to be unique within its office).
function entity_name_exists($table, $nameColumn, $name, $extraWhere = null, $extraParams = array())
{
    $where = "$nameColumn = ?";
    $params = array($name);
    if ($extraWhere !== null) {
        $where .= " and $extraWhere";
        $params = array_merge($params, $extraParams);
    }
    return tc_select_value($nameColumn, $table, $where, $params) !== null;
}

function tc_delete($from, $where, $params = array(), $types = null)
{
    global $db_prefix;
    return tc_query("DELETE FROM `{$db_prefix}$from` WHERE $where", $params, $types);
}

function tc_insert_strings($db, $keyvals)
{
    global $db_prefix;
    $keys = '';
    $places = '';
    $types = '';
    $values = array();
    foreach ($keyvals as $key => $value) {
        if (!empty($keys)) {
            $keys .= ",";
            $places .= ",";
        }
        $keys .= "`$key`";
        $places .= "?";
        $types .= "s";
        // Preserve an explicit null (e.g. value_or_null()) as a real SQL
        // NULL -- "$value" would coerce it to '', defeating callers that
        // rely on NULL to avoid colliding on a UNIQUE column.
        $values[] = is_null($value) ? null : "$value";
    }
    tc_execute("INSERT INTO `{$db_prefix}$db` ($keys) VALUES ($places)", $values, $types);
    return mysqli_insert_id($GLOBALS["___mysqli_ston"]);
}

function tc_update_strings($db, $keyvals, $where = '1=1', $bind = array(), $types = null)
{
    global $db_prefix;
    $places = '';
    $set_types = '';
    $values = array();
    foreach ($keyvals as $key => $value) {
        if (!empty($places)) {
            $places .= ",";
        }
        $places .= "`$key` = ?";
        $set_types .= "s";
        // See tc_insert_strings() -- keep an explicit null as a real SQL
        // NULL instead of coercing it to ''.
        $values[] = is_null($value) ? null : "$value";
    }
    if (!is_array($bind)) {
        $bind = array($bind);
    }
    if (!is_null($types)) {
        $types = $set_types . $types;
    }
    tc_execute("UPDATE `{$db_prefix}$db` SET $places WHERE $where", array_merge($values, $bind), $types);
}

function tc_hash_password($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function tc_is_legacy_password_hash($hash)
{
    // Legacy PHP Timeclock password hashes are the 13-character output of
    // crypt($password, 'xy'). Hashes from password_hash() always start with "$".
    return $hash !== null && $hash !== '' && $hash[0] !== '$';
}

function tc_verify_password($password, $hash)
{
    if (tc_is_legacy_password_hash($hash)) {
        return hash_equals($hash, crypt($password, $hash));
    }

    return password_verify($password, $hash);
}

function tc_maybe_upgrade_password($empfullname, $password, $hash)
{
    // Transparently migrate a verified legacy crypt() hash to password_hash()
    // so accounts don't need to be reset when this upgrade ships.
    if (tc_is_legacy_password_hash($hash)) {
        tc_update_strings("employees", array("employee_passwd" => tc_hash_password($password)), "empfullname = ?", $empfullname);
    }
}

function is_valid_time_of_day($value)
{
    // Accepts "HH:MM" (what <input type="time"> submits) or "HH:MM:SS".
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $value);
}

function get_employee_schedule($empfullname)
{
    // Returns [day_of_week => ['start_time' => 'HH:MM:SS', 'end_time' => 'HH:MM:SS']]
    // for each day $empfullname is scheduled to work. day_of_week follows PHP's
    // date('w') convention: 0 = Sunday .. 6 = Saturday. A day with no entry here
    // is a day off. An end_time earlier than start_time means the shift crosses
    // midnight (e.g. an overnight shift).
    $result = tc_select('day_of_week, start_time, end_time', 'schedules', 'empfullname = ?', $empfullname);
    $schedule = array();
    while ($row = mysqli_fetch_array($result)) {
        $schedule[(int) $row['day_of_week']] = array(
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
        );
    }
    return $schedule;
}

function set_employee_schedule($empfullname, array $days)
{
    // Replace $empfullname's entire weekly schedule with $days -- an array of
    // [day_of_week => ['start_time' => .., 'end_time' => ..]], see
    // get_employee_schedule(). Not transactional (this app's tables are
    // MyISAM), but scoped to one employee's rows, matching the
    // delete-then-reinsert pattern already used elsewhere in this app for
    // replacing a whole set of related rows at once.
    tc_delete('schedules', 'empfullname = ?', $empfullname);
    foreach ($days as $day_of_week => $times) {
        tc_insert_strings('schedules', array(
            'empfullname' => $empfullname,
            'day_of_week' => $day_of_week,
            'start_time' => $times['start_time'],
            'end_time' => $times['end_time'],
        ));
    }
}

function time_of_day_to_seconds($time_str)
{
    // Parse a TIME column value ("HH:MM:SS" or "HH:MM") into seconds since midnight.
    sscanf($time_str, "%d:%d:%d", $hours, $minutes, $seconds);
    return $hours * 3600 + $minutes * 60 + ($seconds ?? 0);
}

function day_start_timestamp($timestamp)
{
    // Local midnight for the day containing $timestamp.
    return mktime(0, 0, 0, (int) date('n', $timestamp), (int) date('j', $timestamp), (int) date('Y', $timestamp));
}

function compute_day_exceptions($date_timestamp, $schedule_day, $punches, $grace_minutes)
{
    // Compute schedule exceptions (absence, late, early) for one calendar day.
    // $date_timestamp is local midnight for that day. $schedule_day is
    // ['start_time' => 'HH:MM:SS', 'end_time' => 'HH:MM:SS'], or null if the
    // employee isn't scheduled to work that day -- no exceptions are possible
    // then, since unscheduled work isn't flagged. $punches is a list of
    // ['in_or_out' => 1|0, 'timestamp' => int] local timestamps for that day,
    // in any order. An end_time at or before start_time means the scheduled
    // shift crosses midnight.
    if ($schedule_day === null) {
        return array();
    }

    $start_secs = time_of_day_to_seconds($schedule_day['start_time']);
    $end_secs = time_of_day_to_seconds($schedule_day['end_time']);
    $scheduled_start = $date_timestamp + $start_secs;
    $scheduled_end = $date_timestamp + $end_secs + ($end_secs <= $start_secs ? 86400 : 0);

    $in_timestamps = array();
    foreach ($punches as $punch) {
        if ($punch['in_or_out'] == 1) {
            $in_timestamps[] = $punch['timestamp'];
        }
    }

    if (empty($in_timestamps)) {
        return array(array('type' => 'absence'));
    }

    $exceptions = array();
    $grace_seconds = $grace_minutes * 60;

    $first_in = min($in_timestamps);
    if ($first_in > $scheduled_start + $grace_seconds) {
        $exceptions[] = array(
            'type' => 'late',
            'minutes' => (int) round(($first_in - $scheduled_start) / 60),
        );
    }

    // Only the day's truly last punch can represent when they left for the
    // day -- an earlier "out" (e.g. a lunch break) followed by a later "in"
    // must not be mistaken for an early departure.
    $last_punch = $punches[0];
    foreach ($punches as $punch) {
        if ($punch['timestamp'] > $last_punch['timestamp']) {
            $last_punch = $punch;
        }
    }
    if ($last_punch['in_or_out'] == 0 && $last_punch['timestamp'] < $scheduled_end - $grace_seconds) {
        $exceptions[] = array(
            'type' => 'early',
            'minutes' => (int) round(($scheduled_end - $last_punch['timestamp']) / 60),
        );
    }

    return $exceptions;
}

function get_employee_exceptions($empfullname, $from_timestamp, $to_timestamp, $tzo, $grace_minutes)
{
    // Compute every schedule exception for $empfullname between the UTM
    // $from_timestamp (inclusive) and $to_timestamp (exclusive), applying the
    // client/server timezone offset $tzo the same way the rest of the
    // reports/ pages do. Returns a flat list of exceptions, each augmented
    // with 'date' (Ymd, local) and 'empfullname'.
    global $db_prefix, $default_in_or_out;

    $schedule = get_employee_schedule($empfullname);
    $fallback_in_or_out = $default_in_or_out ?? 1;

    $query = "select {$db_prefix}info.timestamp as timestamp,
                     coalesce({$db_prefix}punchlist.in_or_out, $fallback_in_or_out) as in_or_out
              from {$db_prefix}info
              left join {$db_prefix}punchlist on {$db_prefix}info.inout = {$db_prefix}punchlist.punchitems
              where {$db_prefix}info.fullname = ? and {$db_prefix}info.timestamp >= ? and {$db_prefix}info.timestamp < ?
              order by {$db_prefix}info.timestamp";
    $result = tc_query($query, array($empfullname, $from_timestamp, $to_timestamp));

    $punches = array();
    while ($row = mysqli_fetch_array($result)) {
        $punches[] = array(
            'in_or_out' => (int) $row['in_or_out'],
            'timestamp' => (int) $row['timestamp'] + $tzo,
        );
    }

    $exceptions = array();
    $day_timestamp = day_start_timestamp($from_timestamp + $tzo);
    $end_day_timestamp = day_start_timestamp($to_timestamp + $tzo);
    // Rolls forward to the end of the previous day's punch window -- kept
    // even with, not always equal to, that day's own midnight, so an
    // overnight shift's post-midnight closing punch is claimed by the day
    // it belongs to instead of bleeding into the next day's window too.
    $window_start = $day_timestamp;
    while ($day_timestamp < $end_day_timestamp) {
        $date = date('Ymd', $day_timestamp);
        $day_of_week = (int) date('w', $day_timestamp);
        $schedule_day = $schedule[$day_of_week] ?? null;

        $window_end = $day_timestamp + 86400;
        if ($schedule_day !== null) {
            $start_secs = time_of_day_to_seconds($schedule_day['start_time']);
            $end_secs = time_of_day_to_seconds($schedule_day['end_time']);
            if ($end_secs <= $start_secs) {
                // Overnight shift: extend the window through the scheduled
                // end time on the following calendar day.
                $window_end = $day_timestamp + 86400 + $end_secs;
            }
        }

        $day_punches = array_values(array_filter(
            $punches,
            function ($punch) use ($window_start, $window_end) {
                return $punch['timestamp'] >= $window_start && $punch['timestamp'] < $window_end;
            }
        ));

        foreach (compute_day_exceptions($day_timestamp, $schedule_day, $day_punches, $grace_minutes) as $exception) {
            $exception['date'] = $date;
            $exception['empfullname'] = $empfullname;
            $exceptions[] = $exception;
        }

        $window_start = $window_end;
        $day_timestamp += 86400;
    }

    return $exceptions;
}

function parse_report_date($date_str, $calendar_style)
{
    // Parse a report "From Date"/"To Date" field (mm/dd/yyyy or dd/mm/yyyy,
    // matching $calendar_style) into a local-midnight timestamp, or null if
    // the string isn't a plausibly valid date in that format.
    if (empty($date_str) || !preg_match('/^([0-9]{1,2})[-\/.]([0-9]{1,2})[-\/.]([0-9]{2}|[0-9]{4})$/', $date_str, $regs)) {
        return null;
    }

    if ($calendar_style == "euro") {
        [$day, $month, $year] = [$regs[1], $regs[2], $regs[3]];
    } else {
        [$month, $day, $year] = [$regs[1], $regs[2], $regs[3]];
    }

    if ($month > 12 || $day > 31) {
        return null;
    }

    return mktime(0, 0, 0, (int) $month, (int) $day, (int) $year);
}

function btag($tag, $attr = array())
{
    $begin = array(htmlentities($tag));
    foreach ($attr as $key => $value) {
        $begin[] = htmlentities($key) . "=\"" . htmlentities($value) . "\"";
    }
    return "<" . implode(" ", $begin) . ">";
}

function tag($tag, $content = "", $attr = array())
{
    return btag($tag, $attr) . htmlentities($content) . "</" . htmlentities($tag) . ">";
}

function html_options($result, $selected = '')
{
    $rv = array();
    while ($row = mysqli_fetch_array($result)) {
        $value = htmlentities($row[0]);
        $display = htmlentities(is_null(@$row[1]) ? $row[0] : $row[1]);
        $sel = ($row[0] == $selected) ? " selected" : "";
        $rv[] = "<option value=\"$value\"$sel>$display</option>\n";
    }
    return implode("", $rv);
}

function yes_no_bool($val, $default = false)
{
    if (strtolower((string) @$val) == 'yes') {
        return true;
    }
    if (strtolower((string) @$val) == 'no') {
        return false;
    }
    return $default;
}

function value_or_null($val)
{
    return (strlen(trim((string) @$val)) == 0) ? null : $val;
}

function has_value($val)
{
    return strlen(trim((string) @$val)) != 0;
}

/*
 * $_POST/$_GET are trusted throughout this codebase to hold plain scalar
 * values (`$var = $_POST['x'];`), which then flow unguarded into
 * string-only functions like preg_match()/stripslashes()/htmlentities().
 * PHP happily accepts fieldname[]=x in a request body/query string and
 * populates $_POST['fieldname']/$_GET['fieldname'] with an actual array
 * instead -- isset() is still true, but the downstream string function
 * throws a fatal TypeError. These normalize a superglobal read to always
 * be a string, treating "submitted as something other than a string" the
 * same as "not submitted at all".
 */
function post_string($key, $default = '')
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? $value : $default;
}

function get_string($key, $default = '')
{
    $value = $_GET[$key] ?? $default;

    return is_string($value) ? $value : $default;
}

// Same as post_string()/get_string(), for the punchclock ajax endpoints that
// accept a value via either GET or POST ($_REQUEST).
function request_string($key, $default = '')
{
    $value = $_REQUEST[$key] ?? $default;

    return is_string($value) ? $value : $default;
}

/*
 * The mirror-image bug: a handful of fields (config settings like $links,
 * name="links[]" style multi-value inputs) are expected to always be an
 * array, and get passed straight to count()/array-index access. Submitting
 * the field as a plain scalar (links=foo instead of links[]=foo) makes
 * count() throw a fatal TypeError under PHP 8 for the same reason
 * post_string()/get_string() exist -- the field's actual submitted type
 * doesn't match what the code assumes.
 */
function post_array($key, $default = [])
{
    $value = $_POST[$key] ?? $default;

    return is_array($value) ? $value : $default;
}

// True if $fullname's most recent punch strictly before $timestamp was an
// "in" -- i.e. a shift that was still genuinely open going into $timestamp.
// The hours-worked reports (total_hours.php, get_csv.php) credit an
// employee's day-start "out" punch with time back to midnight on the
// assumption that they'd been clocked in continuously since before then;
// this lets that assumption be checked against a real punch instead of
// applied unconditionally, which previously credited seconds-to-hours of
// phantom time whenever a day's first captured punch was "out" with no
// preceding "in" at all.
function had_open_shift_before($fullname, $timestamp)
{
    global $db_prefix;
    $result = tc_query(
        "select " . $db_prefix . "punchlist.in_or_out
         from " . $db_prefix . "info, " . $db_prefix . "punchlist
         where " . $db_prefix . "info.fullname = ? and " . $db_prefix . "info.timestamp < ?
         and " . $db_prefix . "info.`inout` = " . $db_prefix . "punchlist.punchitems
         order by " . $db_prefix . "info.timestamp desc limit 1",
        array($fullname, $timestamp)
    );
    $row = mysqli_fetch_array($result);
    return $row !== null && $row['in_or_out'] == '1';
}

function secsToHours($secs, $round_time)
{

    /* The logic for this function was written by Adam Woodbeck, who initially wrote it to round to the
       nearest 15 minutes. It has been expanded to round to the nearest 5, 10, 20, and 30 minutes, as well
       as giving the option to not round at all. */

    /* This function will convert seconds to hours in decimal form */

    $hours = $secs / 3600.0;
    $mins = ($secs % 3600.0) / 60.0;
    $hours = floor($hours);

    /* Add the minutes back on as a percentage of an hour (e.g. 8.25 hours == 8 hours, 15 minutes) */

    if ($round_time == '1') {
        if ($mins >= 57.5) {
            $hours += 1.0;
        } elseif ($mins >= 52.5) {
            $hours += 0.92;
        } elseif ($mins >= 47.5) {
            $hours += 0.83;
        } elseif ($mins >= 42.5) {
            $hours += 0.75;
        } elseif ($mins >= 37.5) {
            $hours += 0.67;
        } elseif ($mins >= 32.5) {
            $hours += 0.58;
        } elseif ($mins >= 27.5) {
            $hours += 0.50;
        } elseif ($mins >= 22.5) {
            $hours += 0.42;
        } elseif ($mins >= 17.5) {
            $hours += 0.33;
        } elseif ($mins >= 12.5) {
            $hours += 0.25;
        } elseif ($mins >= 7.5) {
            $hours += 0.17;
        } elseif ($mins >= 2.5) {
            $hours += 0.08;
        }
    } elseif ($round_time == '2') {
        if ($mins >= 55.0) {
            $hours += 1.0;
        } elseif ($mins >= 45.0) {
            $hours += 0.83;
        } elseif ($mins >= 35.0) {
            $hours += 0.67;
        } elseif ($mins >= 25.0) {
            $hours += 0.50;
        } elseif ($mins >= 15.0) {
            $hours += 0.33;
        } elseif ($mins >= 5.0) {
            $hours += 0.17;
        }
    } elseif ($round_time == '3') {
        if ($mins >= 52.5) {
            $hours += 1.0;
        } elseif ($mins >= 37.5) {
            $hours += 0.75;
        } elseif ($mins >= 22.5) {
            $hours += 0.5;
        } elseif ($mins >= 7.5) {
            $hours += 0.25;
        }
    } elseif ($round_time == '4') {
        if ($mins >= 50.0) {
            $hours += 1.0;
        } elseif ($mins >= 30.0) {
            $hours += 0.67;
        } elseif ($mins >= 10.0) {
            $hours += 0.33;
        }
    } elseif ($round_time == '5') {
        if ($mins >= 45.0) {
            $hours += 1.0;
        } elseif ($mins >= 15.0) {
            $hours += 0.5;
        }
    } elseif (empty($round_time)) {
        $hours += $mins / 60.0;
        $hours = round($hours, 2);
    }

    return number_format($hours, 2);
}

function disabled_acct($get_user)
{

    $result = tc_select("empfullname, disabled", "employees", "empfullname = ?", $get_user);

    while ($row = mysqli_fetch_array($result)) {
        if ("" . $row["disabled"] . "" == 1) {
            echo "<table width=100% border=0 cellpadding=7 cellspacing=1>\n";
            echo "  <tr class=right_main_text><td height=10 align=center valign=top scope=row class=title_underline>The account for " . htmlspecialchars($get_user) . " is
                disabled</td></tr>\n";
            echo "  <tr class=right_main_text>\n";
            echo "    <td align=center valign=top scope=row>\n";
            echo "      <table width=300 border=0 cellpadding=5 cellspacing=0>\n";
            echo "        <tr class=right_main_text><td align=center>Either re-enable the account or go back to the <a class=admin_headings
                      href='timeadmin.php'>\"Add/Edit/Delete Time\"</a> page and choose an account that is not disabled.</td></tr>\n";
            echo "      </table><br /></td></tr></table>\n";
            exit;
        }
    }
}

function get_ipaddress()
{

    if (empty($REMOTE_ADDR)) {
        if (!empty($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
            $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        } elseif (!empty($_ENV) && isset($_ENV['REMOTE_ADDR'])) {
            $REMOTE_ADDR = $_ENV['REMOTE_ADDR'];
        } elseif (@getenv('REMOTE_ADDR')) {
            $REMOTE_ADDR = getenv('REMOTE_ADDR');
        }
    }
    if (empty($HTTP_X_FORWARDED_FOR)) {
        if (!empty($_SERVER) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $HTTP_X_FORWARDED_FOR = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_ENV) && isset($_ENV['HTTP_X_FORWARDED_FOR'])) {
            $HTTP_X_FORWARDED_FOR = $_ENV['HTTP_X_FORWARDED_FOR'];
        } elseif (@getenv('HTTP_X_FORWARDED_FOR')) {
            $HTTP_X_FORWARDED_FOR = getenv('HTTP_X_FORWARDED_FOR');
        }
    }
    if (empty($HTTP_X_FORWARDED)) {
        if (!empty($_SERVER) && isset($_SERVER['HTTP_X_FORWARDED'])) {
            $HTTP_X_FORWARDED = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_ENV) && isset($_ENV['HTTP_X_FORWARDED'])) {
            $HTTP_X_FORWARDED = $_ENV['HTTP_X_FORWARDED'];
        } elseif (@getenv('HTTP_X_FORWARDED')) {
            $HTTP_X_FORWARDED = getenv('HTTP_X_FORWARDED');
        }
    }
    if (empty($HTTP_FORWARDED_FOR)) {
        if (!empty($_SERVER) && isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $HTTP_FORWARDED_FOR = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_ENV) && isset($_ENV['HTTP_FORWARDED_FOR'])) {
            $HTTP_FORWARDED_FOR = $_ENV['HTTP_FORWARDED_FOR'];
        } elseif (@getenv('HTTP_FORWARDED_FOR')) {
            $HTTP_FORWARDED_FOR = getenv('HTTP_FORWARDED_FOR');
        }
    }
    if (empty($HTTP_FORWARDED)) {
        if (!empty($_SERVER) && isset($_SERVER['HTTP_FORWARDED'])) {
            $HTTP_FORWARDED = $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_ENV) && isset($_ENV['HTTP_FORWARDED'])) {
            $HTTP_FORWARDED = $_ENV['HTTP_FORWARDED'];
        } elseif (@getenv('HTTP_FORWARDED')) {
            $HTTP_FORWARDED = getenv('HTTP_FORWARDED');
        }
    }
    if (empty($HTTP_VIA)) {
        if (!empty($_SERVER) && isset($_SERVER['HTTP_VIA'])) {
            $HTTP_VIA = $_SERVER['HTTP_VIA'];
        } elseif (!empty($_ENV) && isset($_ENV['HTTP_VIA'])) {
            $HTTP_VIA = $_ENV['HTTP_VIA'];
        } elseif (@getenv('HTTP_VIA')) {
            $HTTP_VIA = getenv('HTTP_VIA');
        }
    }
    if (empty($HTTP_X_COMING_FROM)) {
        if (!empty($_SERVER) && isset($_SERVER['HTTP_X_COMING_FROM'])) {
            $HTTP_X_COMING_FROM = $_SERVER['HTTP_X_COMING_FROM'];
        } elseif (!empty($_ENV) && isset($_ENV['HTTP_X_COMING_FROM'])) {
            $HTTP_X_COMING_FROM = $_ENV['HTTP_X_COMING_FROM'];
        } elseif (@getenv('HTTP_X_COMING_FROM')) {
            $HTTP_X_COMING_FROM = getenv('HTTP_X_COMING_FROM');
        }
    }
    if (empty($HTTP_COMING_FROM)) {
        if (!empty($_SERVER) && isset($_SERVER['HTTP_COMING_FROM'])) {
            $HTTP_COMING_FROM = $_SERVER['HTTP_COMING_FROM'];
        } elseif (!empty($_ENV) && isset($_ENV['HTTP_COMING_FROM'])) {
            $HTTP_COMING_FROM = $_ENV['HTTP_COMING_FROM'];
        } elseif (@getenv('HTTP_COMING_FROM')) {
            $HTTP_COMING_FROM = getenv('HTTP_COMING_FROM');
        }
    }

    // Gets the default ip sent by the user //

    if (!empty($REMOTE_ADDR)) {
        $direct_ip = $REMOTE_ADDR;
    }

    // Gets the proxy ip sent by the user //

    $proxy_ip = '';
    if (!empty($HTTP_X_FORWARDED_FOR)) {
        $proxy_ip = $HTTP_X_FORWARDED_FOR;
    } elseif (!empty($HTTP_X_FORWARDED)) {
        $proxy_ip = $HTTP_X_FORWARDED;
    } elseif (!empty($HTTP_FORWARDED_FOR)) {
        $proxy_ip = $HTTP_FORWARDED_FOR;
    } elseif (!empty($HTTP_FORWARDED)) {
        $proxy_ip = $HTTP_FORWARDED;
    } elseif (!empty($HTTP_VIA)) {
        $proxy_ip = $HTTP_VIA;
    } elseif (!empty($HTTP_X_COMING_FROM)) {
        $proxy_ip = $HTTP_X_COMING_FROM;
    } elseif (!empty($HTTP_COMING_FROM)) {
        $proxy_ip = $HTTP_COMING_FROM;
    }

    // Returns the true IP if it has been found, else FALSE //

    if (empty($proxy_ip)) {
        // True IP without proxy
        return $direct_ip;
    } else {
        $is_ip = preg_match('|^([0-9]{1,3}\.){3,3}[0-9]{1,3}|', $proxy_ip, $regs);
        if ($is_ip && (count($regs) > 0)) {
            // True IP behind a proxy
            return $regs[0];
        } else {
            // Can't define IP: there is a proxy but we don't have
            // information about the true IP
            return false;
        }
    }
}

function ip_range($network, $ip)
{

    /**
     * Based on IP Pattern Matcher
     * Originally by J.Adams <jna@retina.net>
     * Found on <http://www.php.net/manual/en/function.ip2long.php>
     * Modified by Robbat2 <robbat2@users.sourceforge.net>
     *
     * Matches:
     * xxx.xxx.xxx.xxx        (exact)
     * xxx.xxx.xxx.[yyy-zzz]  (range)
     * xxx.xxx.xxx.xxx/nn     (CIDR)
     *
     * Does not match:
     * xxx.xxx.xxx.xx[yyy-zzz]  (range, partial octets not supported)
     *
     * @param   string   string of IP range to match
     * @param   string   string of IP to test against range
     *
     * @return  boolean    always true
     *
     * @access  public
     */

    $result = true;

    if (preg_match('|([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/([0-9]+)|', $network, $regs)) {
        // performs a mask match
        $ipl = ip2long($ip);
        $rangel = ip2long($regs[1] . '.' . $regs[2] . '.' . $regs[3] . '.' . $regs[4]);

        $maskl = 0;

        for ($i = 0; $i < 31; $i++) {
            if ($i < $regs[5] - 1) {
                $maskl = $maskl + pow(2, (30 - $i));
            }
        }

        if (($maskl & $rangel) == ($maskl & $ipl)) {
            return true;
        } else {
            return false;
        }
    } else {
        // range based
        $maskocts = explode('.', $network);
        $ipocts = explode('.', $ip);

        // perform a range match
        for ($i = 0; $i < 4; $i++) {
            if (preg_match('|\[([0-9]+)\-([0-9]+)\]|', $maskocts[$i], $regs)) {
                if (
                    ($ipocts[$i] > $regs[2])
                    || ($ipocts[$i] < $regs[1])
                ) {
                    $result = false;
                } // end if
            } else {
                if ($maskocts[$i] <> $ipocts[$i]) {
                    $result = false;
                }
            }
        }
    }

    return $result;
}

function setTimeZone()
{

    global $use_client_tz;
    global $use_server_tz;
    global $tzo;

    if ($use_client_tz == "yes") {
        if (isset($_COOKIE['tzoffset'])) {
            $tzo = $_COOKIE['tzoffset'];
            settype($tzo, "integer");
            $tzo = $tzo * 60;
        } else {
            $tzo = 0;
        }
    } elseif ($use_server_tz == "yes") {
        $tzo = date('Z');
    } else {
        $tzo = 0;
    }
}

/*
 * Shared left-nav sidebar for admin/timeadd.php, admin/timeedit.php, and
 * admin/timedelete.php, which otherwise each duplicate this ~40-line block
 * verbatim (and previously reflected $get_user into it unescaped). $current
 * is 'add', 'edit', or 'delete', selecting which of the three links gets the
 * "current page" highlight class.
 */
function admin_time_left_nav($get_user, $current)
{
    return "<table width=100% height=89% border=0 cellpadding=0 cellspacing=1>\n"
        . "  <tr valign=top>\n"
        . "    <td class=left_main width=180 align=left scope=col>\n"
        . "      <table class=hide width=100% border=0 cellpadding=1 cellspacing=0>\n"
        . "        <tr><td class=left_rows height=11></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle>Users</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/user.png' alt='User Summary' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='useradmin.php'>User Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/user_add.png' alt='Create New User' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='usercreate.php'>Create New User</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/magnifier.png' alt='User Search' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='usersearch.php'>User Search</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle>Offices</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/brick.png' alt='Office Summary' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='officeadmin.php'>Office Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/brick_add.png' alt='Create New Office' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='officecreate.php'>Create New Office</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle>Groups</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/group.png' alt='Group Summary' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='groupadmin.php'>Group Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/group_add.png' alt='Create New Group' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='groupcreate.php'>Create New Group</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle colspan=2>In/Out Status</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/application.png' alt='Status Summary' />\n"
        . "                &nbsp;&nbsp;<a class=admin_headings href='statusadmin.php'>Status Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/application_add.png' alt='Create Status' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='statuscreate.php'>Create Status</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle colspan=2>Miscellaneous</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/clock.png' alt='Add/Edit/Delete Time' />\n"
        . "                &nbsp;&nbsp;<a class=admin_headings href='timeadmin.php'>Add/Edit/Delete Time</a></td></tr>\n"
        . admin_time_sidebar_links($get_user, $current)
        . "        <tr><td class=left_rows_border_top height=18 align=left valign=middle><img src='../images/icons/application_edit.png'\n"
        . "                alt='Edit System Settings' /> &nbsp;&nbsp;<a class=admin_headings href='sysedit.php'>Edit System Settings</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/database_go.png'\n"
        . "                alt='Upgrade Database' />&nbsp;&nbsp;&nbsp;<a class=admin_headings href='dbupgrade.php'>Upgrade Database</a></td></tr>\n"
        . "      </table></td>\n";
}

function admin_time_sidebar_links($get_user, $current)
{
    $u = htmlspecialchars(rawurlencode($get_user));
    $pages = [
        'add' => ['timeadd.php', 'Add Time'],
        'edit' => ['timeedit.php', 'Edit Time'],
        'delete' => ['timedelete.php', 'Delete Time'],
    ];

    $html = '';
    foreach ($pages as $key => [$page, $label]) {
        $class = $key === $current ? 'current_left_rows_indent' : 'left_rows_indent';
        $html .= "        <tr><td class=$class height=18 align=left valign=middle><img src='../images/icons/arrow_right.png' alt='$label' />\n"
            . "                &nbsp;&nbsp;<a class=admin_headings href=\"$page?username=$u\">$label</a></td></tr>\n";
    }

    return $html;
}

/*
 * Shared left-nav sidebar for admin/scheduleedit.php, following the same
 * pattern as admin_time_left_nav() above -- a single shared function instead
 * of duplicating this ~40-line block into another file.
 */
function admin_schedule_left_nav($get_user)
{
    $u = htmlspecialchars(rawurlencode($get_user));

    return "<table width=100% height=89% border=0 cellpadding=0 cellspacing=1>\n"
        . "  <tr valign=top>\n"
        . "    <td class=left_main width=180 align=left scope=col>\n"
        . "      <table class=hide width=100% border=0 cellpadding=1 cellspacing=0>\n"
        . "        <tr><td class=left_rows height=11></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle>Users</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/user.png' alt='User Summary' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='useradmin.php'>User Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows_indent height=18 align=left valign=middle><img src='../images/icons/arrow_right.png' alt='Edit User' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href=\"useredit.php?username=$u\">Edit User</a></td></tr>\n"
        . "        <tr><td class=current_left_rows_indent height=18 align=left valign=middle><img src='../images/icons/arrow_right.png' alt='Schedule' />\n"
        . "                &nbsp;&nbsp;<a class=admin_headings href=\"scheduleedit.php?username=$u\">Schedule</a></td></tr>\n"
        . "        <tr><td class=left_rows_indent height=18 align=left valign=middle><img src='../images/icons/arrow_right.png' alt='Delete User' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href=\"userdelete.php?username=$u\">Delete User</a></td></tr>\n"
        . "        <tr><td class=left_rows_border_top height=18 align=left valign=middle><img src='../images/icons/user_add.png' alt='Create New User' />\n"
        . "                &nbsp;&nbsp;<a class=admin_headings href='usercreate.php'>Create New User</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/magnifier.png' alt='User Search' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='usersearch.php'>User Search</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle>Offices</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/brick.png' alt='Office Summary' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='officeadmin.php'>Office Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/brick_add.png' alt='Create New Office' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='officecreate.php'>Create New Office</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle>Groups</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/group.png' alt='Group Summary' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='groupadmin.php'>Group Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/group_add.png' alt='Create New Group' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='groupcreate.php'>Create New Group</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle colspan=2>In/Out Status</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/application.png' alt='Status Summary' />\n"
        . "                &nbsp;&nbsp;<a class=admin_headings href='statusadmin.php'>Status Summary</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/application_add.png' alt='Create Status' />&nbsp;&nbsp;\n"
        . "                <a class=admin_headings href='statuscreate.php'>Create Status</a></td></tr>\n"
        . "        <tr><td class=left_rows height=33></td></tr>\n"
        . "        <tr><td class=left_rows_headings height=18 valign=middle colspan=2>Miscellaneous</td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/clock.png' alt='Add/Edit/Delete Time' />\n"
        . "                &nbsp;&nbsp;<a class=admin_headings href='timeadmin.php'>Add/Edit/Delete Time</a></td></tr>\n"
        . "        <tr><td class=left_rows_border_top height=18 align=left valign=middle><img src='../images/icons/application_edit.png'\n"
        . "                alt='Edit System Settings' /> &nbsp;&nbsp;<a class=admin_headings href='sysedit.php'>Edit System Settings</a></td></tr>\n"
        . "        <tr><td class=left_rows height=18 align=left valign=middle><img src='../images/icons/database_go.png'\n"
        . "                alt='Upgrade Database' />&nbsp;&nbsp;&nbsp;<a class=admin_headings href='dbupgrade.php'>Upgrade Database</a></td></tr>\n"
        . "      </table></td>\n";
}

// One sidebar link for admin/leftnav_bootstrap.php, highlighted "active" when
// $href matches the page currently being rendered.
function admin_leftnav_link($href, $label, $current)
{
    // Bootstrap's built-in .active sets a solid background + white text,
    // which fights with the flush/transparent list style used here (and
    // reads as white-on-white). Bold + a text color reads as "current page"
    // without that clash.
    $active = ($current === $href) ? ' fw-bold text-primary' : '';
    $safe_href = htmlspecialchars($href);
    $safe_label = htmlspecialchars($label);

    return "    <a href=\"$safe_href\" class=\"list-group-item list-group-item-action bg-transparent$active\">$safe_label</a>\n";
}
