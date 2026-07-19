<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_date_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Add Time</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const WHERE_EMPFULLNAME = "empfullname = ?";
const FOOTER_PHP = 'footer_bootstrap.php';
const MSG_SOMETHING_FISHY = "Something is fishy here.\n";
const DATE_PATTERN = "/^([0-9]{1,2})[\-\/\.]([0-9]{1,2})[\-\/\.](([0-9]{2})|([0-9]{4}))$/i";
const PUNCHLIST_ORDER_BY_PUNCHITEMS = "1=1 order by punchitems asc";

function render_time_add_form(
    $self,
    $js_datefmt,
    $post_username,
    $post_displayname,
    $post_date,
    $post_time,
    $timefmt_size,
    $timefmt_24hr_text,
    $get_user,
    $timefmt_24hr,
    $post_statusname,
    $post_notes
) {
    $h_timefmt_size = htmlentities($timefmt_size);
    $h_timefmt_24hr_text = htmlentities($timefmt_24hr_text);
    $h_js_datefmt = htmlentities($js_datefmt);

    echo "      <form name='form' action='$self' method='post' onsubmit=\"return isDate()\">\n";
    echo csrf_field() . "\n";
    echo "        <input type='hidden' name='date_format' value=\"$h_js_datefmt\">\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Username</label>\n";
    echo "          <input type='hidden' name='post_username' value=\"" . htmlentities($post_username) . "\">\n";
    echo "          <div class=\"form-control-plaintext\">" . htmlentities($post_username) . "</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Display Name</label>\n";
    echo "          <input type='hidden' name='post_displayname' value=\"" . htmlentities($post_displayname) . "\">\n";
    echo "          <div class=\"form-control-plaintext\">" . htmlentities($post_displayname) . "</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_date'>Date <span class=\"text-danger\">*</span></label>\n";
    echo "          <div class=\"input-group\" style=\"max-width:300px;\">\n";
    echo "            <input type='text' id='post_date' class=\"form-control\" size='10' maxlength='10' name='post_date' value=\""
        . htmlentities($post_date) . "\">\n";
    echo "            <a href=\"#\" class=\"btn btn-outline-secondary\"
                onclick=\"cal.select(document.forms['form'].post_date,'post_date_anchor','$h_js_datefmt');
                return false;\" name=\"post_date_anchor\" id=\"post_date_anchor\">Pick Date</a>\n";
    echo "          </div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_time'>Time <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' id='post_time' class=\"form-control\" style=\"max-width:200px;\" size='10' maxlength=\"$h_timefmt_size\"
                name='post_time' value=\"" . htmlentities($post_time) . "\">\n";
    echo "          <div class=\"form-text\">$h_timefmt_24hr_text</div>\n";
    echo "        </div>\n";
    echo "        <input type='hidden' name='get_user' value=\"" . htmlentities($get_user) . "\">\n";
    echo "        <input type='hidden' name='timefmt_24hr' value=\"" . htmlentities($timefmt_24hr) . "\">\n";
    echo "        <input type='hidden' name='timefmt_24hr_text' value=\"$h_timefmt_24hr_text\">\n";
    echo "        <input type='hidden' name='timefmt_size' value=\"$h_timefmt_size\">\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_statusname'>Status <span class=\"text-danger\">*</span></label>\n";
    echo "          <select id='post_statusname' class=\"form-select\" name='post_statusname'>\n";
    echo "            <option value='1'>Choose One</option>\n";
    echo html_options(tc_select("punchitems", "punchlist", PUNCHLIST_ORDER_BY_PUNCHITEMS), $post_statusname);
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_notes'>Notes</label>\n";
    echo "          <input type='text' id='post_notes' class=\"form-control\" size='17' maxlength='250' name='post_notes' value=\""
        . htmlspecialchars($post_notes, ENT_QUOTES) . "\">\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <div style=\"position:absolute;visibility:hidden;background-color:#ffffff;\" id=\"mydiv\">&nbsp;</div>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Add Time'>Add Time</button>\n";
    echo "        <a href='timeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
}

if (($timefmt == "G:i") || ($timefmt == "H:i")) {
    $timefmt_24hr = '1';
    $timefmt_24hr_text = '24 hr format';
    $timefmt_size = '5';
} else {
    $timefmt_24hr = '0';
    $timefmt_24hr_text = '12 hr format';
    $timefmt_size = '8';
}

require_once '../lib/auth.php';
require_time_admin();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    if (!isset($_GET['username'])) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"alert alert-danger\">\n";
        echo "    <h5>PHP Timeclock Error!</h5>\n";
        echo "    <p>How did you get here?</p>\n";
        echo "    <p>Go back to the <a href='timeadmin.php'>Add/Edit/Delete Time</a> page to add a time.</p>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    $get_user = stripslashes(get_string('username'));

    disabled_acct($get_user);

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    // admin_leftnav_time_context needs raw (unescaped) values -- see the
    // doc-comment in leftnav_bootstrap.php.
    $admin_leftnav_time_context = array('username' => $get_user, 'current' => 'timeadd.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $get_user = addslashes($get_user);

    $result = tc_select("*", "employees", "empfullname = ? order by empfullname", $get_user);

    while ($row = mysqli_fetch_array($result)) {
        $username = stripslashes("" . $row['empfullname'] . "");
        $displayname = stripslashes("" . $row['displayname'] . "");
    }

    $get_user = stripslashes(get_string('username'));

    echo "      <h5><img src='../images/icons/clock_add.png'> Add Time</h5>\n";
    echo "      <form name='form' action='$self' method='post' onsubmit=\"return isDate()\">\n";
    echo csrf_field() . "\n";
    echo "        <input type='hidden' name='date_format' value='$js_datefmt'>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Username</label>\n";
    echo "          <input type='hidden' name='post_username' value=\"" . htmlentities($username) . "\">\n";
    echo "          <div class=\"form-control-plaintext\">" . htmlentities($username) . "</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Display Name</label>\n";
    echo "          <input type='hidden' name='post_displayname' value=\"" . htmlentities($displayname) . "\">\n";
    echo "          <div class=\"form-control-plaintext\">" . htmlentities($displayname) . "</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_date'>Date ($tmp_datefmt) <span class=\"text-danger\">*</span></label>\n";
    echo "          <div class=\"input-group\" style=\"max-width:300px;\">\n";
    echo "            <input type='text' id='post_date' class=\"form-control\" size='10' maxlength='10' name='post_date'>\n";
    echo "            <a href=\"#\" class=\"btn btn-outline-secondary\"
                onclick=\"form.post_date.value='';cal.select(document.forms['form'].post_date,'post_date_anchor','$js_datefmt');
                return false;\" name=\"post_date_anchor\" id=\"post_date_anchor\">Pick Date</a>\n";
    echo "          </div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_time'>Time <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' id='post_time' class=\"form-control\" style=\"max-width:200px;\" size='10' maxlength='$timefmt_size' name='post_time'>\n";
    echo "          <div class=\"form-text\">$timefmt_24hr_text</div>\n";
    echo "        </div>\n";
    echo "        <input type='hidden' name='get_user' value=\"" . htmlentities($get_user) . "\">\n";
    echo "        <input type='hidden' name='timefmt_24hr' value=\"$timefmt_24hr\">\n";
    echo "        <input type='hidden' name='timefmt_24hr_text' value=\"$timefmt_24hr_text\">\n";
    echo "        <input type='hidden' name='timefmt_size' value=\"$timefmt_size\">\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_statusname'>Status <span class=\"text-danger\">*</span></label>\n";
    echo "          <select id='post_statusname' class=\"form-select\" name='post_statusname'>\n";
    echo "            <option value='1'>Choose One</option>\n";
    echo html_options(tc_select("punchitems", "punchlist", PUNCHLIST_ORDER_BY_PUNCHITEMS));
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_notes'>Notes</label>\n";
    echo "          <input type='text' id='post_notes' class=\"form-control\" size='17' maxlength='250' name='post_notes'>\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <div style=\"position:absolute;visibility:hidden;background-color:#ffffff;\" id=\"mydiv\">&nbsp;</div>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Add Time'>Add Time</button>\n";
    echo "        <a href='timeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $get_user = stripslashes(post_string('get_user'));
    $post_username = stripslashes(post_string('post_username'));
    $post_displayname = stripslashes(post_string('post_displayname'));
    $post_date = post_string('post_date');
    $post_time = post_string('post_time');
    $post_statusname = post_string('post_statusname');
    $post_notes = post_string('post_notes');
    $timefmt_24hr = $_POST['timefmt_24hr'];
    $timefmt_24hr_text = $_POST['timefmt_24hr_text'];
    $timefmt_size = $_POST['timefmt_size'];
    $date_format = $_POST['date_format'];

    $get_user = addslashes($get_user);
    $post_username = addslashes($post_username);
    $post_displayname = addslashes($post_displayname);

    // begin post validation //

    if (!empty($get_user)) {
        $result = tc_select("*", "employees", WHERE_EMPFULLNAME, $get_user);
        while ($row = mysqli_fetch_array($result)) {
            $tmp_get_user = "" . $row['empfullname'] . "";
        }
        if (!isset($tmp_get_user)) {
            echo MSG_SOMETHING_FISHY;
            exit;
        }
    }

    if (!empty($post_username)) {
        $result = tc_select("*", "employees", WHERE_EMPFULLNAME, $post_username);
        while ($row = mysqli_fetch_array($result)) {
            $tmp_username = "" . $row['empfullname'] . "";
        }
        if (!isset($tmp_username)) {
            echo MSG_SOMETHING_FISHY;
            exit;
        }
    }

    if (!empty($post_displayname)) {
        $result = tc_select("*", "employees", "empfullname = ? and displayname = ?", array($post_username, $post_displayname));
        while ($row = mysqli_fetch_array($result)) {
            $tmp_post_displayname = "" . $row['displayname'] . "";
        }
        if (!isset($tmp_post_displayname)) {
            echo MSG_SOMETHING_FISHY;
            exit;
        }
    }

    if (!empty($post_statusname)) {
        if ($post_statusname != '1') {
            $result = tc_select("*", "punchlist", "punchitems = ?", $post_statusname);

            while ($row = mysqli_fetch_array($result)) {
                $punchitems = "" . $row['punchitems'] . "";
                $color = "" . $row['color'] . "";
            }
            if (!isset($punchitems)) {
                echo MSG_SOMETHING_FISHY;
                exit;
            }
        } else {
            $punchitems = '1';
        }
    }

    if (($timefmt == "G:i") || ($timefmt == "H:i")) {
        $tmp_timefmt_24hr = '1';
        $tmp_timefmt_24hr_text = '24 hr format';
        $tmp_timefmt_size = '5';
    } else {
        $tmp_timefmt_24hr = '0';
        $tmp_timefmt_24hr_text = '12 hr format';
        $tmp_timefmt_size = '8';
    }

    if (($timefmt_24hr != $tmp_timefmt_24hr) || ($timefmt_24hr_text != $tmp_timefmt_24hr_text) || ($timefmt_size != $tmp_timefmt_size)) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }
    if ($date_format != $js_datefmt) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }

    if ($post_notes == "") {
        $post_notes = " ";
    }

    // end post validation //

    $get_user = stripslashes($get_user);
    $post_username = stripslashes($post_username);
    $post_displayname = stripslashes($post_displayname);

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_time_context = array('username' => $get_user, 'current' => 'timeadd.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    echo "      <h5><img src='../images/icons/clock_add.png'> Add Time</h5>\n";

    if (
        (empty($post_date)) || (empty($post_time)) || ($post_statusname == '1') || (!preg_match('/' . "^([[:alnum:]]| |-|_|\.)+$" . '/i', $post_statusname)) ||
        (!preg_match(DATE_PATTERN, $post_date))
    ) {
        $evil_post = '1';
        if (empty($post_date) || !preg_match(DATE_PATTERN, $post_date)) {
            echo "      <div class=\"alert alert-danger\">A valid Date is required.</div>\n";
        } elseif (empty($post_time)) {
            echo "      <div class=\"alert alert-danger\">A valid Time is required.</div>\n";
        } elseif ($post_statusname == "1") {
            echo "      <div class=\"alert alert-danger\">A Status must be chosen.</div>\n";
        } elseif (!preg_match('/' . "^([[:alnum:]]| |-|_|\.)+$" . '/i', $post_statusname)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, underscores, spaces, and periods are allowed
                    in a Status Name.</div>\n";
        }
    } elseif ($timefmt_24hr == '0') {
        if (
            (!preg_match('/' . "^([0-9]?[0-9])+:+([0-9]+[0-9])+([a|p]+m)$" . '/i', $post_time, $time_regs)) && (!preg_match(
                '/' . "^([0-9]?[0-9])+:+([0-9]+[0-9])+( [a|p]+m)$" . '/i',
                $post_time,
                $time_regs
            ))
        ) {
            $evil_time = '1';
            echo "      <div class=\"alert alert-danger\">A valid Time is required.</div>\n";
        } else {
            if (isset($time_regs)) {
                $h = $time_regs[1];
                $m = $time_regs[2];
            }
            $h = $time_regs[1];
            $m = $time_regs[2];
            if (($h > 12) || ($m > 59)) {
                $evil_time = '1';
                echo "      <div class=\"alert alert-danger\">A valid Time is required.</div>\n";
            }
        }
    } elseif ($timefmt_24hr == '1') {
        if (!preg_match('/' . "^([0-9]?[0-9])+:+([0-9]+[0-9])$" . '/i', $post_time, $time_regs)) {
            $evil_time = '1';
            echo "      <div class=\"alert alert-danger\">A valid Time is required.</div>\n";
        } else {
            if (isset($time_regs)) {
                $h = $time_regs[1];
                $m = $time_regs[2];
            }
            $h = $time_regs[1];
            $m = $time_regs[2];
            if (($h > 24) || ($m > 59)) {
                $evil_time = '1';
                echo "      <div class=\"alert alert-danger\">A valid Time is required.</div>\n";
            }
        }
    }

    if (preg_match(DATE_PATTERN, $post_date, $date_regs)) {
        if ($calendar_style == "amer") {
            if (isset($date_regs)) {
                $month = $date_regs[1];
                $day = $date_regs[2];
                $year = $date_regs[3];
            }
            if ($month > 12 || $day > 31) {
                $evil_date = '1';
                if (!isset($evil_post)) {
                    echo "      <div class=\"alert alert-danger\">A valid Date is required.</div>\n";
                }
            }
        } elseif ($calendar_style == "euro") {
            if (isset($date_regs)) {
                $month = $date_regs[2];
                $day = $date_regs[1];
                $year = $date_regs[3];
            }
            if ($month > 12 || $day > 31) {
                $evil_date = '1';
                if (!isset($evil_post)) {
                    echo "      <div class=\"alert alert-danger\">A valid Date is required.</div>\n";
                }
            }
        }
    }

    if ((isset($evil_post)) || (isset($evil_date)) || (isset($evil_time))) {
        render_time_add_form(
            $self,
            $js_datefmt,
            $post_username,
            $post_displayname,
            $post_date,
            $post_time,
            $timefmt_size,
            $timefmt_24hr_text,
            $get_user,
            $timefmt_24hr,
            $post_statusname,
            $post_notes
        );
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    } else {
        $post_username = addslashes($post_username);
        $post_displayname = addslashes($post_displayname);

        // configure timestamp to insert/update

        if ($calendar_style == "euro") {
            $post_date = "$month/$day/$year";
        } elseif ($calendar_style == "amer") {
            $post_date = "$month/$day/$year";
        }

        $timestamp = strtotime($post_date . " " . $post_time) - $tzo;

        // check for duplicate time for $post_username

        $result = tc_select("*", "info", "fullname = ?", $post_username);

        $post_username = stripslashes($post_username);
        $post_displayname = stripslashes($post_displayname);

        while ($row = mysqli_fetch_array($result)) {
            $info_table_timestamp = "" . $row['timestamp'] . "";
            if ($timestamp == $info_table_timestamp) {
                echo "      <div class=\"alert alert-danger\">Duplicate time exists for this user on this date. Time not added.</div>\n";
                render_time_add_form(
                    $self,
                    $js_datefmt,
                    $post_username,
                    $post_displayname,
                    $post_date,
                    $post_time,
                    $timefmt_size,
                    $timefmt_24hr_text,
                    $get_user,
                    $timefmt_24hr,
                    $post_statusname,
                    $post_notes
                );
                echo "    </div>\n";
                echo "  </div>\n";
                echo "</div>\n";
                include_once FOOTER_PHP;
                exit;
            }
        }

        // check to see if this would be the most recent time for $post_username. if so, run the update query for the employees table.

        $post_username = addslashes($post_username);
        $post_displayname = addslashes($post_displayname);

        $result = tc_select("*", "employees", WHERE_EMPFULLNAME, $post_username);

        while ($row = mysqli_fetch_array($result)) {
            $employees_table_timestamp = "" . $row['tstamp'] . "";
        }

        if ($timestamp > $employees_table_timestamp) {
            tc_update_strings("employees", array("tstamp" => $timestamp), WHERE_EMPFULLNAME, $post_username);
        }

        // determine who the authenticated user is for audit log

        $user = current_admin_username();

        // configure current time to insert for audit log

        $time = time();
        $time_hour = gmdate('H', $time);
        $time_min = gmdate('i', $time);
        $time_sec = gmdate('s', $time);
        $time_month = gmdate('m', $time);
        $time_day = gmdate('d', $time);
        $time_year = gmdate('Y', $time);
        $time_tz_stamp = mktime($time_hour, $time_min, $time_sec, $time_month, $time_day, $time_year);

        // this needs to be changed later
        $post_why = "";

        // add the time to the info table for $post_username

        tc_insert_strings("info", array(
            "fullname" => $post_username,
            "inout" => $post_statusname,
            "timestamp" => $timestamp,
            "notes" => $post_notes
        ));

        // add the results to the audit table

        if (strtolower($ip_logging) == "yes") {
            tc_insert_strings("audit", array(
                "modified_by_ip" => $connecting_ip,
                "modified_by_user" => $user,
                "modified_when" => $time_tz_stamp,
                "modified_from" => '0',
                "modified_to" => $timestamp,
                "modified_why" => $post_why,
                "user_modified" => $post_username
            ));
        } else {
            tc_insert_strings("audit", array(
                "modified_by_user" => $user,
                "modified_when" => $time_tz_stamp,
                "modified_from" => '0',
                "modified_to" => $timestamp,
                "modified_why" => $post_why,
                "user_modified" => $post_username
            ));
        }

        $post_username = stripslashes($post_username);
        $post_displayname = stripslashes($post_displayname);
        $post_date = date($datefmt, $timestamp + $tzo);

        echo "      <div class=\"alert alert-success\">Time added successfully.</div>\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Username:</th><td>" . htmlentities($post_username) . "</td></tr>\n";
        echo "        <tr><th>Display Name:</th><td>" . htmlentities($post_displayname) . "</td></tr>\n";
        echo "        <tr><th>Date:</th><td>" . htmlentities($post_date) . "</td></tr>\n";
        echo "        <tr><th>Time:</th><td>" . htmlentities($post_time) . "</td></tr>\n";
        echo "        <tr><th>Status:</th><td style=\"color:" . htmlentities($color) . ";\">" . htmlentities($post_statusname) . "</td></tr>\n";
        echo "        <tr><th>Notes:</th><td>" . htmlspecialchars($post_notes) . "</td></tr>\n";
        echo "      </table>\n";
        echo "      <a href='timeadmin.php' class=\"btn btn-primary\">Done</a>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }
}
