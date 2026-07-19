<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_date_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Edit Time</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const WHERE_EMPFULLNAME = "empfullname = ?";
const FOOTER_PHP = 'footer_bootstrap.php';
const MSG_SOMETHING_FISHY = "Something is fishy here.\n";

function hidden_field($name, $value)
{
    return "        <input type='hidden' name='$name' value=\"" . htmlspecialchars($value) . "\">\n";
}

function form_open($self, $with_onsubmit = false)
{
    $onsubmit = $with_onsubmit ? ' onsubmit="return isDate()"' : '';
    return "      <form name='form' action='" . htmlspecialchars($self) . "' method='post'$onsubmit>\n";
}

function render_date_search_form($self, $js_datefmt, $tmp_datefmt, $post_username, $post_displayname, $post_date, $get_user, $clear_date_first)
{
    $onclick_clear = $clear_date_first ? "form.post_date.value='';" : '';

    echo form_open($self, true);
    echo csrf_field() . "\n";
    echo "        <input type='hidden' name='date_format' value='" . htmlentities($js_datefmt) . "'>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Username</label>\n";
    echo "          <input type='hidden' name='post_username' value=\"" . htmlspecialchars($post_username) . "\">\n";
    echo "          <div class=\"form-control-plaintext\">" . htmlspecialchars($post_username) . "</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Display Name</label>\n";
    echo "          <input type='hidden' name='post_displayname' value=\"" . htmlspecialchars($post_displayname) . "\">\n";
    echo "          <div class=\"form-control-plaintext\">" . htmlspecialchars($post_displayname) . "</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_date'>Date ($tmp_datefmt) <span class=\"text-danger\">*</span></label>\n";
    echo "          <div class=\"input-group\" style=\"max-width:300px;\">\n";
    echo "            <input type='text' id='post_date' class=\"form-control\" size='10' maxlength='10' name='post_date' value=\""
        . htmlspecialchars($post_date) . "\">\n";
    echo "            <a href=\"#\" class=\"btn btn-outline-secondary\"
                onclick=\"{$onclick_clear}cal.select(document.forms['form'].post_date,'post_date_anchor','$js_datefmt');
                return false;\" name=\"post_date_anchor\" id=\"post_date_anchor\">Pick Date</a>\n";
    echo "          </div>\n";
    echo "        </div>\n";
    echo hidden_field('get_user', $get_user);
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <div style=\"position:absolute;visibility:hidden;background-color:#ffffff;\" id=\"mydiv\">&nbsp;</div>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit Time'>Edit Time</button>\n";
    echo "        <a href='timeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
}

function render_punch_edit_rows(
    $final_num_rows,
    $final_username,
    $final_inout,
    $final_notes,
    $final_mysql_timestamp,
    $final_time,
    $edit_time_textbox,
    $timefmt_size,
    $color1,
    $color2
) {
    $row_count = 0;
    for ($x = 0; $x < $final_num_rows; $x++) {
        $row_color = ($row_count % 2) ? $color1 : $color2;
        $statuscolor = tc_select_value("color", "punchlist", "punchitems = ?", $final_inout[$x]);
        $textbox_value = isset($edit_time_textbox[$x]) ? $edit_time_textbox[$x] : '';

        echo "        <tr style=\"background-color:$row_color;\">\n";
        echo "          <td><input type='text' class=\"form-control form-control-sm\" size='7' maxlength='$timefmt_size'
                name='edit_time_textbox[$x]' value=\"" . htmlspecialchars($textbox_value) . "\"></td>\n";
        echo "          <td style=\"color:" . htmlspecialchars($statuscolor) . ";\">" . htmlspecialchars($final_inout[$x]) . "</td>\n";
        echo "          <td>" . htmlspecialchars($final_time[$x]) . "</td>\n";
        echo "          <td>" . htmlspecialchars($final_notes[$x]) . "</td>\n";
        echo "        </tr>\n";
        echo "        <input type='hidden' name='final_username[$x]' value=\"" . htmlspecialchars($final_username[$x]) . "\">\n";
        echo "        <input type='hidden' name='final_inout[$x]' value=\"" . htmlspecialchars($final_inout[$x]) . "\">\n";
        echo "        <input type='hidden' name='final_notes[$x]' value=\"" . htmlspecialchars($final_notes[$x]) . "\">\n";
        echo "        <input type='hidden' name='final_time[$x]' value=\"" . htmlspecialchars($final_time[$x]) . "\">\n";
        echo "        <input type='hidden' name='final_mysql_timestamp[$x]' value=\"" . htmlspecialchars($final_mysql_timestamp[$x]) . "\">\n";
        $row_count++;
    }
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
        echo "    <p>Go back to the <a href='timeadmin.php'>Add/Edit/Delete Time</a> page to edit a time.</p>\n";
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
    $admin_leftnav_time_context = array('username' => $get_user, 'current' => 'timeedit.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $get_user = addslashes($get_user);

    $result = tc_select("*", "employees", "empfullname = ? order by empfullname", $get_user);

    while ($row = mysqli_fetch_array($result)) {
        $username = stripslashes("" . $row['empfullname'] . "");
        $displayname = stripslashes("" . $row['displayname'] . "");
    }
    $get_user = stripslashes($get_user);

    echo "      <h5><img src='../images/icons/clock_edit.png'> Edit Time</h5>\n";
    render_date_search_form($self, $js_datefmt, $tmp_datefmt, $username, $displayname, '', $get_user, true);
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
    @$final_username = $_POST['final_username'];
    @$final_inout = $_POST['final_inout'];
    @$final_notes = $_POST['final_notes'];
    @$final_mysql_timestamp = $_POST['final_mysql_timestamp'];
    @$final_num_rows = $_POST['final_num_rows'];
    @$final_time = $_POST['final_time'];
    @$edit_time_textbox = $_POST['edit_time_textbox'];
    @$timestamp = $_POST['timestamp'];
    @$calc = $_POST['calc'];
    $row_count = '0';
    $cnt = '0';

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

    // end post validation //

    $get_user = stripslashes($get_user);
    $post_username = stripslashes($post_username);
    $post_displayname = stripslashes($post_displayname);

    // begin post validation //

    if ($get_user != $post_username) {
        exit;
    }

    // end post validation //

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_time_context = array('username' => $get_user, 'current' => 'timeedit.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    echo "      <h5><img src='../images/icons/clock_edit.png'> Edit Time</h5>\n";

    // begin post validation //

    if (empty($post_date)) {
        $evil_post = '1';
        echo "      <div class=\"alert alert-danger\">A valid Date is required.</div>\n";
    } elseif (preg_match('/' . "^([0-9]{1,2})[-\,\/,.]([0-9]{1,2})[-\,\/,.](([0-9]{2})|([0-9]{4}))$" . '/i', $post_date, $date_regs)) {
        if ($calendar_style == "amer") {
            if (isset($date_regs)) {
                $month = $date_regs[1];
                $day = $date_regs[2];
                $year = $date_regs[3];
            }
            if ($month > 12 || $day > 31) {
                $evil_post = '1';
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
                $evil_post = '1';
                if (!isset($evil_post)) {
                    echo "      <div class=\"alert alert-danger\">A valid Date is required.</div>\n";
                }
            }
        }
    }

    if (isset($evil_post)) {
        render_date_search_form($self, $js_datefmt, $tmp_datefmt, $post_username, $post_displayname, $post_date, $get_user, false);
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;

        // end post validation //
    } else {
        if (isset($_POST['tmp_var'])) {
            // begin post validation //

            if ($_POST['tmp_var'] != '1') {
                echo MSG_SOMETHING_FISHY;
                exit;
            }
            $tmp2_calc = intval($calc);
            $tmp2_timestamp = intval($timestamp);
            if ((strlen($tmp2_calc) != "10") || (!is_integer($tmp2_calc))) {
                echo MSG_SOMETHING_FISHY;
                exit;
            }
            if ((strlen($tmp2_timestamp) != "10") || (!is_integer($tmp2_timestamp))) {
                echo MSG_SOMETHING_FISHY;
                exit;
            }
            if (!is_numeric($final_num_rows)) {
                exit;
            }

            // end post validation //

            for ($x = 0; $x < $final_num_rows; $x++) {
                $final_username[$x] = stripslashes($final_username[$x]);
                $tmp_username = stripslashes($tmp_username);

                if ($final_username[$x] != $tmp_username) {
                    echo "Something is fishy heree.\n";
                    exit;
                }
                $final_mysql_timestamp[$x] = intval($final_mysql_timestamp[$x]);
                if ((strlen($final_mysql_timestamp[$x]) != "10") || (!is_integer($final_mysql_timestamp[$x]))) {
                    echo MSG_SOMETHING_FISHY;
                    exit;
                }

                $result_sel = tc_select("*", "punchlist", "punchitems = ?", $final_inout[$x]);

                while ($row = mysqli_fetch_array($result_sel)) {
                    $punchitems = "" . $row['punchitems'] . "";
                }
                if (!isset($punchitems)) {
                    echo MSG_SOMETHING_FISHY;
                    exit;
                }

                $final_username[$x] = addslashes($final_username[$x]);

                $result5 = tc_select(
                    "*",
                    "info",
                    "(fullname = ?) and (timestamp = ?) and (`inout` = ?)",
                    array($final_username[$x], $final_mysql_timestamp[$x], $final_inout[$x])
                );
                @$tmp_num_rows = mysqli_num_rows($result5);

                if ((isset($tmp_num_rows)) && (@$tmp_num_rows != '1')) {
                    echo MSG_SOMETHING_FISHY;
                    exit;
                }

                if (!empty($edit_time_textbox[$x])) {
                    // configure timestamp to insert/update //

                    if ($calendar_style == "euro") {
                        $post_date = "$month/$day/$year";
                    } elseif ($calendar_style == "amer") {
                        $post_date = "$month/$day/$year";
                    }

                    $tmp_timestamp = strtotime($post_date) - @$tzo;
                    $tmp_calc = $timestamp + 86400 - @$tzo;

                    if (($tmp_timestamp != $timestamp) || ($tmp_calc != $calc)) {
                        echo MSG_SOMETHING_FISHY;
                        exit;
                    }

                    // end post validation //

                    if ($timefmt_24hr == '0') {
                        // 12 Hour with or without leading zeros with upper or lower case AM or PM //
                        if ((!preg_match('/' . "^([0-9]?[0-9])+:+([0-9]+[0-9])+([a|p]+m)$" . '/i', $edit_time_textbox[$x], $time_regs)) && (!preg_match('/' . "^([0-9]?[0-9])+:+([0-9]+[0-9])+( [a|p]+m)$" . '/i', $edit_time_textbox[$x], $time_regs))) {
                            $evil_time = '1';
                        } else {
                            if (isset($time_regs)) {
                                $h = $time_regs[1];
                                $m = $time_regs[2];
                            }
                            $h = $time_regs[1];
                            $m = $time_regs[2];
                            if (($h > 12) || ($m > 59)) {
                                $evil_time = '1';
                            }
                        }
                    } elseif ($timefmt_24hr == '1') {
                        // 24 Hour with or without leading zeros //
                        if (!preg_match('/' . "^([0-2]?[0-9])+:+([0-5]+[0-9])+$" . '/', $edit_time_textbox[$x], $time_regs)) {
                            $evil_time = '1';
                        } else {
                            if (isset($time_regs)) {
                                $h = $time_regs[1];
                                $m = $time_regs[2];
                            }
                            $h = $time_regs[1];
                            $m = $time_regs[2];
                            if (($h > 24) || ($m > 59)) {
                                $evil_time = '1';
                            }
                        }
                    }
                }
            }

            for ($x = 0; $x < $final_num_rows; $x++) {
                if (empty($edit_time_textbox[$x])) {
                    $cnt++;
                }
            }

            if ($cnt == $final_num_rows) {
                $evil_time = '1';
            }

            if (isset($evil_time)) {
                echo "      <div class=\"alert alert-danger\">A valid Time is required.</div>\n";
                echo form_open($self);
                echo csrf_field() . "\n";

                // configure date to display correctly //

                if ($calendar_style == "euro") {
                    $post_date = "$day/$month/$year";
                }

                echo "      <h6><img src='../images/icons/clock_edit.png'> Edit Time for " . htmlspecialchars($post_username) . " on "
                    . htmlspecialchars($post_date) . "</h6>\n";
                echo "      <div class=\"table-responsive\">\n";
                echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
                echo "        <tr>\n";
                echo "          <th>New Time</th>\n";
                echo "          <th>In/Out</th>\n";
                echo "          <th>Current Time</th>\n";
                echo "          <th>Notes</th>\n";
                echo "        </tr>\n";

                for ($x = 0; $x < $final_num_rows; $x++) {
                    $final_username[$x] = stripslashes($final_username[$x]);
                }
                render_punch_edit_rows(
                    $final_num_rows,
                    $final_username,
                    $final_inout,
                    $final_notes,
                    $final_mysql_timestamp,
                    $final_time,
                    $edit_time_textbox,
                    $timefmt_size,
                    $color1,
                    $color2
                );
                echo "      </table>\n";
                echo "      </div>\n";
                $tmp_var = '1';
                echo hidden_field('calc', $calc);
                echo hidden_field('timestamp', $timestamp);
                echo "        <input type='hidden' name='tmp_var' value=\"$tmp_var\">\n";
                echo hidden_field('post_username', $post_username);
                echo hidden_field('post_displayname', $post_displayname);
                echo hidden_field('post_date', $post_date);
                echo hidden_field('get_user', $get_user);
                echo hidden_field('final_num_rows', $final_num_rows);
                echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit Time'>Edit Time</button>\n";
                echo "        <a href='timeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
                echo "      </form>\n";
                echo "    </div>\n";
                echo "  </div>\n";
                echo "</div>\n";
                include_once FOOTER_PHP;
                exit;
            } elseif (!isset($evil_time)) {
                echo "      <div class=\"alert alert-success\">Time edited successfully.</div>\n";
                echo form_open($self);
                echo csrf_field() . "\n";

                // configure date to display correctly //

                if ($calendar_style == "euro") {
                    $post_date = "$day/$month/$year";
                }

                echo "      <h6><img src='../images/icons/clock_edit.png'> Edited Time for " . htmlspecialchars($post_username) . " on "
                    . htmlspecialchars($post_date) . "</h6>\n";
                echo "      <div class=\"table-responsive\">\n";
                echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
                echo "        <tr>\n";
                echo "          <th>&nbsp;</th>\n";
                echo "          <th>New Time</th>\n";
                echo "          <th>In/Out</th>\n";
                echo "          <th>Old Time</th>\n";
                echo "          <th>Notes</th>\n";
                echo "        </tr>\n";

                $new_tstamp = array();

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

                for ($x = 0; $x < $final_num_rows; $x++) {
                    if ($edit_time_textbox[$x] != '') {
                        $row_color = ($row_count % 2) ? $color1 : $color2;

                        $result = tc_select("*", "employees", WHERE_EMPFULLNAME, $final_username[$x]);

                        while ($row = mysqli_fetch_array($result)) {
                            $tmp_tstamp = "" . $row['tstamp'] . "";
                        }

                        // configure timestamp to insert/update //

                        if ($calendar_style == "euro") {
                            $post_date = "$month/$day/$year";
                        } elseif ($calendar_style == "amer") {
                            $post_date = "$month/$day/$year";
                        }

                        $new_tstamp[$x] = strtotime($post_date . " " . $edit_time_textbox[$x]) - $tzo;

                        if ($new_tstamp[$x] > $tmp_tstamp) {
                            tc_update_strings("employees", array("tstamp" => $new_tstamp[$x]), WHERE_EMPFULLNAME, $final_username[$x]);
                        } elseif ($new_tstamp[$x] < $tmp_tstamp) {
                            $result2 = tc_select("*", "info", "fullname = ? order by timestamp desc limit 1,1", $final_username[$x]);

                            while ($row2 = mysqli_fetch_array($result2)) {
                                $tmp_tstamp_2 = "" . $row2['timestamp'] . "";
                            }

                            if ($new_tstamp[$x] > @$tmp_tstamp_2) {
                                tc_update_strings("employees", array("tstamp" => $new_tstamp[$x]), WHERE_EMPFULLNAME, $final_username[$x]);
                            } elseif ($new_tstamp[$x] < @$tmp_tstamp_2) {
                                tc_update_strings("employees", array("tstamp" => $tmp_tstamp_2), WHERE_EMPFULLNAME, $final_username[$x]);
                            }
                        }

                        // A punch with no notes typed stores notes as NULL
                        // (not ''), but $final_notes[$x] round-trips as ''
                        // either way -- match either so a NULL-notes punch
                        // (the common case for regular clock in/out) isn't
                        // silently skipped by this update.
                        tc_update_strings(
                            "info",
                            array("timestamp" => $new_tstamp[$x]),
                            "(fullname = ?) and (`inout` = ?) and (timestamp = ?) and ((notes = ?) or (notes is null and ? = ''))",
                            array($final_username[$x], $final_inout[$x], $final_mysql_timestamp[$x], $final_notes[$x], $final_notes[$x])
                        );

                        // add the results to the audit table

                        if (strtolower($ip_logging) == "yes") {
                            tc_insert_strings("audit", array(
                                "modified_by_ip" => $connecting_ip,
                                "modified_by_user" => $user,
                                "modified_when" => $time_tz_stamp,
                                "modified_from" => $final_mysql_timestamp[$x],
                                "modified_to" => $new_tstamp[$x],
                                "modified_why" => $post_why,
                                "user_modified" => $final_username[$x]
                            ));
                        } else {
                            tc_insert_strings("audit", array(
                                "modified_by_user" => $user,
                                "modified_when" => $time_tz_stamp,
                                "modified_from" => $final_mysql_timestamp[$x],
                                "modified_to" => $new_tstamp[$x],
                                "modified_why" => $post_why,
                                "user_modified" => $final_username[$x]
                            ));
                        }

                        echo "        <tr style=\"background-color:$row_color;\">\n";
                        echo "          <td><img src='../images/icons/accept.png' /></td>\n";
                        echo "          <td>" . htmlspecialchars($edit_time_textbox[$x]) . "</td>\n";
                        echo "          <td>" . htmlspecialchars($final_inout[$x]) . "</td>\n";
                        echo "          <td>" . htmlspecialchars($final_time[$x]) . "</td>\n";
                        echo "          <td>" . htmlspecialchars($final_notes[$x]) . "</td>\n";
                        echo "        </tr>\n";
                        $row_count++;
                    }
                }
                echo "      </table>\n";
                echo "      </div>\n";
                echo "      </form>\n";
                echo "      <a href='timeadmin.php' class=\"btn btn-primary\">Done</a>\n";
                echo "    </div>\n";
                echo "  </div>\n";
                echo "</div>\n";
                include_once FOOTER_PHP;
                exit;
            }
        } else {
            // configure timestamp to insert/update //

            if ($calendar_style == "euro") {
                $post_date = "$month/$day/$year";
            } elseif ($calendar_style == "amer") {
                $post_date = "$month/$day/$year";
            }

            $timestamp = strtotime($post_date) - @$tzo;
            $calc = $timestamp + 86400 - @$tzo;
            $post_username = stripslashes($post_username);
            $post_displayname = stripslashes($post_displayname);
            $post_username = addslashes($post_username);
            $post_displayname = addslashes($post_displayname);

            $result = tc_select(
                "*",
                "info",
                "(fullname = ?) and ((timestamp < ?) and (timestamp >= ?)) order by timestamp asc",
                array($post_username, $calc, $timestamp)
            );

            $username = array();
            $inout = array();
            $notes = array();
            $mysql_timestamp = array();

            while ($row = mysqli_fetch_array($result)) {
                $time_set = '1';
                $username[] = "" . $row['fullname'] . "";
                $inout[] = "" . $row['inout'] . "";
                $notes[] = "" . $row['notes'] . "";
                $mysql_timestamp[] = "" . $row['timestamp'] . "";
            }
            $num_rows = mysqli_num_rows($result);
        }

        $post_username = stripslashes($post_username);
        $post_displayname = stripslashes($post_displayname);

        if (!isset($time_set)) {
            // configure date to display correctly //

            if ($calendar_style == "euro") {
                $post_date = "$day/$month/$year";
            }

            echo "      <div class=\"alert alert-danger\">No time was found in the system for " . htmlspecialchars($post_username) . " on "
                . htmlspecialchars($post_date) . ".</div>\n";
            render_date_search_form($self, $js_datefmt, $tmp_datefmt, $post_username, $post_displayname, $post_date, $get_user, false);
            echo "    </div>\n";
            echo "  </div>\n";
            echo "</div>\n";
            include_once FOOTER_PHP;
            exit;
        }

        echo "      <div class=\"alert alert-info\">Please enter a time in the New Time box or boxes you wish to edit below.</div>\n";
        echo form_open($self);
        echo csrf_field() . "\n";

        // configure date to display correctly //

        if ($calendar_style == "euro") {
            $post_date = "$day/$month/$year";
        }

        echo "      <h6><img src='../images/icons/clock_edit.png'> Edit Time for " . htmlspecialchars($post_username) . " on "
            . htmlspecialchars($post_date) . "</h6>\n";

        if (isset($time_set)) {
            echo "      <div class=\"table-responsive\">\n";
            echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
            echo "        <tr>\n";
            echo "          <th>New Time</th>\n";
            echo "          <th>In/Out</th>\n";
            echo "          <th>Current Time</th>\n";
            echo "          <th>Notes</th>\n";
            echo "        </tr>\n";

            for ($x = 0; $x < $num_rows; $x++) {
                $time[$x] = date("$timefmt", $mysql_timestamp[$x] + $tzo);
                $username[$x] = stripslashes($username[$x]);
            }
            render_punch_edit_rows($num_rows, $username, $inout, $notes, $mysql_timestamp, $time, array(), $timefmt_size, $color1, $color2);
            echo "      </table>\n";
            echo "      </div>\n";
            $tmp_var = '1';
            echo "        <input type='hidden' name='tmp_var' value=\"$tmp_var\">\n";
            echo hidden_field('post_username', $post_username);
            echo hidden_field('post_displayname', $post_displayname);
            echo hidden_field('post_date', $post_date);
            echo "        <input type='hidden' name='num_rows' value=\"$num_rows\">\n";
            echo hidden_field('calc', $calc);
            echo hidden_field('timestamp', $timestamp);
            echo hidden_field('get_user', $get_user);
            echo "        <input type='hidden' name='final_num_rows' value=\"$num_rows\">\n";
            echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit Time'>Edit Time</button>\n";
            echo "        <a href='timeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
            echo "      </form>\n";
            echo "    </div>\n";
            echo "  </div>\n";
            echo "</div>\n";
            include_once FOOTER_PHP;
            exit;
        }
    }
}
