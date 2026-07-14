<?php

include 'config.inc.php';
require_once 'lib/csrf.php';

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

// set cookie if 'Remember Me?' checkbox is checked, or reset cookie if 'Reset Cookie?' is checked //

const WHERE_EMPFULLNAME = "empfullname = ?";

if ($show_display_name == "yes") {
    $emp_name_field = "displayname";
} else {
    $emp_name_field = "empfullname";
}

if ($request == 'POST') {
    if (!verify_csrf_token()) {
        echo "Your session has expired. Please try again.\n";
        exit;
    }

    @$remember_me = $_POST['remember_me'];
    @$reset_cookie = $_POST['reset_cookie'];
    $fullname = post_string('left_fullname');
    $displayname = post_string('left_displayname');
    $barcode = yes_no_bool($barcode_clockin) ? post_string('left_barcode') : "";
    if ((isset($remember_me)) && ($remember_me != '1')) {
        echo "Something is fishy here.\n";
        exit;
    }
    if ((isset($reset_cookie)) && ($reset_cookie != '1')) {
        echo "Something is fishy here.\n";
        exit;
    }

    // begin post validation //
    $errors = array();

    if (has_value($barcode)) {
        $tmp_name = tc_select_value($emp_name_field, "employees", "barcode = ?", $barcode);
        if (!has_value($tmp_name)) {
            $errors[] = "Invalid barcode '" . htmlentities($barcode) . "'";
        } elseif (isset($emp_name) and $emp_name != $tmp_name) {
            $errors[] = "Username / Barcode mismatch";
        } else {
            $emp_name = $tmp_name;
        }
    }

    $tmp_name = '';
    if (yes_no_bool($show_display_name)) {
        if (has_value($displayname)) {
            $tmp_name = tc_select_value($emp_name_field, "employees", "displayname = ?", $displayname);
            if (!has_value($tmp_name)) {
                $errors[] = "Invalid username '" . htmlentities($displayname) . "'";
            }
        }
    } else {
        if (has_value($fullname)) {
            $tmp_name = tc_select_value($emp_name_field, "employees", WHERE_EMPFULLNAME, $fullname);
            if (!has_value($tmp_name)) {
                $errors[] = "Invalid username '" . htmlentities($fullname) . "'";
            }
        }
    }

    if (has_value($tmp_name)) {
        if (isset($emp_name) and $emp_name != $tmp_name) {
            $errors[] = "Username / Barcode mismatch";
        } else {
            $emp_name = $tmp_name;
        }
    }

    // end post validation //

    if (empty($errors)) {
        if (isset($remember_me)) {
            setcookie("remember_me", $emp_name, time() + (60 * 60 * 24 * 365 * 2));
        } elseif (isset($reset_cookie)) {
            setcookie("remember_me", "", time() - 3600);
        }
    }

    ob_end_flush();
}



echo "<div class=\"col-md-3 mb-4\">\n";

// display links in top left of each page //

if ($links != "none") {
    echo "  <ul class=\"list-unstyled small\">\n";

    for ($x = 0; $x < count($display_links); $x++) {
        echo "    <li><a href='$links[$x]' target='_new'>$display_links[$x]</a></li>\n";
    }

    echo "  </ul>\n";
}

// display form to submit signin/signout information //

echo "  <form name='timeclock' action='$self' autocomplete='off' method='post'>\n";
echo csrf_field() . "\n";

if (yes_no_bool($barcode_clockin)) {
    echo <<<BARCODE_CLOCKIN
    <div class="mb-3">
        <label for="left_barcode" class="form-label">Barcode</label>
        <input type="text" id="left_barcode" name="left_barcode" maxlength="250" class="form-control" value="" autocomplete="off" autofocus>
        <input type="text" style="display:none;"><!-- prevent login name auto-fill due to password field below -->
    </div>
BARCODE_CLOCKIN;
}

if (yes_no_bool($barcode_clockin) and yes_no_bool($manual_clockin)) {
    echo "    <hr>\n";
}

if (yes_no_bool($manual_clockin)) {
    echo "    <p class=\"fw-bold\">Please sign in below:</p>\n";
    echo "    <div class=\"mb-3\">\n";
    echo "      <label for=\"left_name_select\" class=\"form-label\">Name</label>\n";

    // query to populate dropdown with employee names //

    if ($show_display_name == "yes") {
        echo "      <select id=\"left_name_select\" class=\"form-select\" name='left_displayname'>\n";
    } else {
        echo "      <select id=\"left_name_select\" class=\"form-select\" name='left_fullname'>\n";
    }

    echo "        <option value =''>...</option>\n";
    echo html_options(
        tc_select($emp_name_field, "employees", "disabled <> '1' AND empfullname <> 'admin' ORDER BY $emp_name_field"),
        @$_COOKIE['remember_me']
    );
    echo "      </select>\n";
    echo "    </div>\n";

    // determine whether to use encrypted passwords or not //

    if ($use_passwd == "yes") {
        echo "    <div class=\"mb-3\">\n";
        echo "      <label for=\"employee_passwd\" class=\"form-label\">Password</label>\n";
        echo "      <input type='password' id=\"employee_passwd\" class=\"form-control\" name='employee_passwd' maxlength='25'>\n";
        echo "    </div>\n";
    }

    echo "    <div class=\"mb-3\">\n";
    echo "      <label for=\"left_inout_select\" class=\"form-label\">In/Out</label>\n";

    // populate dropdown with punchlist items //

    echo "      <select id=\"left_inout_select\" class=\"form-select\" name='left_inout'>\n";
    echo "        <option value =''>...</option>\n";
    echo html_options(tc_select("punchitems", "punchlist"));
    echo "      </select>\n";
    echo "    </div>\n";

    echo "    <div class=\"mb-3\">\n";
    echo "      <label for=\"left_notes\" class=\"form-label\">Notes</label>\n";
    echo "      <input type='text' id=\"left_notes\" class=\"form-control\" name='left_notes' maxlength='250'>\n";
    echo "    </div>\n";

    if (!isset($_COOKIE['remember_me'])) {
        echo "    <div class=\"form-check mb-3\">\n";
        echo "      <input type='checkbox' class=\"form-check-input\" id=\"remember_me\" name='remember_me' value='1'>\n";
        echo "      <label class=\"form-check-label\" for=\"remember_me\">Remember Me?</label>\n";
        echo "    </div>\n";
    } elseif (isset($_COOKIE['remember_me'])) {
        echo "    <div class=\"form-check mb-3\">\n";
        echo "      <input type='checkbox' class=\"form-check-input\" id=\"reset_cookie\" name='reset_cookie' value='1'>\n";
        echo "      <label class=\"form-check-label\" for=\"reset_cookie\">Reset Cookie?</label>\n";
        echo "    </div>\n";
    }
}

echo "    <button type='submit' class=\"btn btn-primary\" name='submit_button' value='Submit'>Submit</button>\n";
echo "  </form>\n";

if (yes_no_bool($display_weather)) {
    echo "  <div class=\"mt-3\">\n";
    include 'sidebar-metar-display.php';
    echo "  </div>\n";
}

echo "</div>\n";

if ($request == 'POST') {
    // signin/signout data passed over from timeclock.php //

    $inout = post_string('left_inout');
    $notes = preg_replace('/[^[:alnum:] \,\.\?-]/', "", strtolower(post_string('left_notes')));

    // begin post validation //

    # Trying to toggle, look up the "punchnext" toggle state:
    if (!has_value($inout) and has_value($emp_name)) {
        $result = tc_query(<<<QUERY
   SELECT p.punchnext
     FROM {$db_prefix}employees AS e
LEFT JOIN {$db_prefix}info      AS i ON (e.empfullname = i.fullname AND e.tstamp = i.timestamp)
LEFT JOIN {$db_prefix}punchlist AS p ON (i.inout = p.punchitems)
    WHERE e.$emp_name_field = ?
QUERY
        , $emp_name);
        while ($row = mysqli_fetch_array($result)) {
            $inout = $row[0];
        }
    } elseif (has_value($inout)) {
        $inout = tc_select_value("punchitems", "punchlist", "punchitems = ?", $inout);
        if (!has_value($inout)) {
            echo "In/Out Status is not in the database.\n";
            exit;
        }
    }

    if ($use_passwd == "yes") {
        $employee_passwd = post_string('employee_passwd');
    }

    // end post validation //

    if (!has_value($emp_name) && !has_value($inout)) {
        $errors[] = "You have not chosen a username or a status. Please try again.";
    } elseif (!has_value($emp_name)) {
        $errors[] = "You have not chosen a username. Please try again.";
    } elseif (!has_value($inout)) {
        $errors[] = "You have not chosen a status. Please try again.";
    }

    if (!empty($errors)) {
        echo "    <div class=\"col-md-9\">\n";
        echo "      <div class=\"alert alert-danger\">" . implode("<br>\n", $errors) . "</div>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include 'footer_bootstrap.php';
        exit;
    }

    // configure timestamp to insert/update //

    $time = time();
    $hour = gmdate('H', $time);
    $min = gmdate('i', $time);
    $sec = gmdate('s', $time);
    $month = gmdate('m', $time);
    $day = gmdate('d', $time);
    $year = gmdate('Y', $time);
    $tz_stamp = mktime($hour, $min, $sec, $month, $day, $year);

    if (has_value($barcode) or $use_passwd == "no") {
        if (!has_value($fullname)) {
            $fullname = tc_select_value("empfullname", "employees", "$emp_name_field = ?", $emp_name);
        }

        $clockin = array("fullname" => $fullname, "inout" => $inout, "timestamp" => $tz_stamp, "notes" => $notes);
        if (strtolower($ip_logging) == "yes") {
            $clockin["ipaddress"] = $connecting_ip;
        }

        tc_insert_strings("info", $clockin);
        tc_update_strings("employees", array("tstamp" => $tz_stamp), WHERE_EMPFULLNAME, $fullname);

        echo "<head>\n";
        echo "<meta http-equiv='refresh' content=0;url=index.php>\n";
        echo "</head>\n";
    } else {
        $sel_result = tc_select(
            "empfullname, employee_passwd",
            "employees",
            "$emp_name_field = ?",
            $emp_name
        );
        while ($row = mysqli_fetch_array($sel_result)) {
            $tmp_password = "" . $row["employee_passwd"] . "";
            $fullname = "" . $row["empfullname"] . "";
        }

        if (tc_verify_password($employee_passwd, $tmp_password)) {
            tc_maybe_upgrade_password($fullname, $employee_passwd, $tmp_password);

            $clockin = array("fullname" => $fullname, "inout" => $inout, "timestamp" => $tz_stamp, "notes" => $notes);
            if (strtolower($ip_logging) == "yes") {
                $clockin["ipaddress"] = $connecting_ip;
            }

            tc_insert_strings("info", $clockin);
            tc_update_strings("employees", array("tstamp" => $tz_stamp), WHERE_EMPFULLNAME, $fullname);

            echo "<head>\n";
            echo "<meta http-equiv='refresh' content=0;url=index.php>\n";
            echo "</head>\n";
        } else {
            echo "    <div class=\"col-md-9\">\n";
            echo "      <div class=\"alert alert-danger\">You have entered the wrong password for "
                . htmlentities($emp_name) . ". Please try again.</div>\n";
            echo "    </div>\n";
            echo "  </div>\n";
            echo "</div>\n";
            include 'footer_bootstrap.php';
            exit;
        }
    }
}
