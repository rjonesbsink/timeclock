<?php

require_once '../lib/session.php';
start_secure_session();

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
const FOOTER_PHP = 'footer_bootstrap.php';
const WHERE_EMPFULLNAME = "empfullname = ?";

include_once '../config.inc.php';
if ($request !== 'POST') {
    include_once 'header_get_bootstrap.php';
    include_once 'topmain_bootstrap.php';
}
echo "<title>$title - Edit User</title>\n";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    if (!isset($_GET['username'])) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"alert alert-danger\">\n";
        echo "    <h5>PHP Timeclock Error!</h5>\n";
        echo "    <p>How did you get here?</p>\n";
        echo "    <p>Go back to the <a href='useradmin.php'>User Summary</a> page to edit users.</p>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    $get_user = htmlentities(get_string('username'));
    $get_office = htmlentities(get_string('officename'));

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_user_context = array('username' => $get_user, 'officename' => $get_office, 'current' => 'useredit.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $row_count = 0;
    $result = tc_select("*", "employees", WHERE_EMPFULLNAME, $get_user);

    while ($row = mysqli_fetch_array($result)) {
        $row_count++;
        $row_color = ($row_count % 2) ? $color2 : $color1;

        $username = htmlentities("" . $row['empfullname'] . "");
        $displayname = htmlentities("" . $row['displayname'] . "");
        $user_email = htmlentities("" . $row['email'] . "");
        $user_barcode = htmlentities("" . $row['barcode'] . "");
        $groups_tmp = htmlentities("" . $row['groups'] . "");
        $office = htmlentities("" . $row['office'] . "");
        $admin = "" . $row['admin'] . "";
        $reports = "" . $row['reports'] . "";
        $time_admin = "" . $row['time_admin'] . "";
        $disabled = "" . $row['disabled'] . "";
    }
    ((mysqli_free_result($result) || (is_object($result) && (get_class($result) == "mysqli_result"))) ? true : false);

    // make sure you cannot edit the admin perms for the last admin user in the system!! //

    if (!empty($admin)) {
        @$admin_count_rows = mysqli_num_rows(tc_select("empfullname", "employees", "admin = '1'"));
        if (@$admin_count_rows == "1") {
            $evil = "1";
        }
    }

    echo "      <h5><img src='../images/icons/user_edit.png'> Edit User</h5>\n";

    if (isset($evil)) {
        echo "      <div class=\"alert alert-danger\">Cannot edit the Sys Admin properties of this user as this user is the last Sys Admin User
                in the system. Go back and give another user Sys Admin privileges before attempting to edit the Sys Admin properties of this
                user.</div>\n";
    }

    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Username</label>\n";
    echo "          <input type='hidden' name='post_username' value=\"$username\">\n";
    echo "          <div class=\"form-control-plaintext\">$username</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Display Name <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='50' name='display_name' value=\"$displayname\">\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Email Address <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='75' name='email_addy' value='$user_email'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Barcode</label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='75' name='barcode' value='$user_barcode'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Office <span class=\"text-danger\">*</span></label>\n";
    echo "          <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
    echo "            <option selected>$office</option>\n";
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Group <span class=\"text-danger\">*</span></label>\n";
    if ($groups_tmp == "") {
        echo "          <select class=\"form-select\" name='group_name' onfocus='group_names();'>\n";
        echo "            <option selected>&nbsp;</option>\n";
    } else {
        echo "          <select class=\"form-select\" name='group_name' onfocus='group_names();'>\n";
        echo "            <option selected>$groups_tmp</option>\n";
    }
    echo "          </select>\n";
    echo "        </div>\n";

    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Sys Admin User?</label>\n";
    $disabled_attr = isset($evil) ? " disabled" : "";
    if ($admin == "1") {
        echo "          <div class=\"form-check form-check-inline\"><input$disabled_attr type='radio' class=\"form-check-input\" name='admin_perms' value='1'
                    checked id='admin_perms_y'><label class=\"form-check-label\" for='admin_perms_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input$disabled_attr type='radio' class=\"form-check-input\" name='admin_perms' value='0'
                    id='admin_perms_n'><label class=\"form-check-label\" for='admin_perms_n'>No</label></div>\n";
    } else {
        echo "          <div class=\"form-check form-check-inline\"><input$disabled_attr type='radio' class=\"form-check-input\" name='admin_perms' value='1'
                    id='admin_perms_y'><label class=\"form-check-label\" for='admin_perms_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input$disabled_attr type='radio' class=\"form-check-input\" name='admin_perms' value='0'
                    checked id='admin_perms_n'><label class=\"form-check-label\" for='admin_perms_n'>No</label></div>\n";
    }
    echo "        </div>\n";

    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Time Admin User?</label>\n";
    if ($time_admin == "1") {
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='1'
                    checked id='time_admin_perms_y'><label class=\"form-check-label\" for='time_admin_perms_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='0'
                    id='time_admin_perms_n'><label class=\"form-check-label\" for='time_admin_perms_n'>No</label></div>\n";
    } else {
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='1'
                    id='time_admin_perms_y'><label class=\"form-check-label\" for='time_admin_perms_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='0'
                    checked id='time_admin_perms_n'><label class=\"form-check-label\" for='time_admin_perms_n'>No</label></div>\n";
    }
    echo "        </div>\n";

    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Reports User?</label>\n";
    if ($reports == "1") {
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='1'
                    checked id='reports_perms_y'><label class=\"form-check-label\" for='reports_perms_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='0'
                    id='reports_perms_n'><label class=\"form-check-label\" for='reports_perms_n'>No</label></div>\n";
    } else {
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='1'
                    id='reports_perms_y'><label class=\"form-check-label\" for='reports_perms_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='0'
                    checked id='reports_perms_n'><label class=\"form-check-label\" for='reports_perms_n'>No</label></div>\n";
    }
    echo "        </div>\n";

    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">User Account Disabled?</label>\n";
    if ($disabled == "1") {
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='1'
                    checked id='disabled_y'><label class=\"form-check-label\" for='disabled_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='0'
                    id='disabled_n'><label class=\"form-check-label\" for='disabled_n'>No</label></div>\n";
    } else {
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='1'
                    id='disabled_y'><label class=\"form-check-label\" for='disabled_y'>Yes</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='0'
                    checked id='disabled_n'><label class=\"form-check-label\" for='disabled_n'>No</label></div>\n";
    }
    echo "        </div>\n";

    echo "        <p class=\"small text-muted\">* required</p>\n";
    if (isset($evil)) {
        echo "        <input type='hidden' name='evil' value='$evil'>\n";
    }
    echo "        <input type='hidden' name='get_office' value='$get_office'>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit User'>Edit User</button>\n";
    echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    include_once 'header_post_bootstrap.php';
    include_once 'topmain_bootstrap.php';

    $post_username = post_string('post_username');
    $display_name = post_string('display_name');
    $email_addy = post_string('email_addy');
    $user_barcode = value_or_null(post_string('barcode'));// UNIQUE constraint so no empty strings
    $office_name = post_string('office_name');
    $get_office = htmlentities(post_string('get_office'));
    $group_name = post_string('group_name');
    $admin_perms = post_string('admin_perms');
    $reports_perms = post_string('reports_perms');
    $time_admin_perms = post_string('time_admin_perms');
    $post_disabled = post_string('disabled');
    @$evil = $_POST['evil'];

    if (isset($evil)) {
        if ($evil != '1') {
            echo "Something is fishy here.";
            exit;
        }
    }

    if (isset($evil)) {
        $admin_perms = "1";
    }

    if (!empty($post_username)) {
        $tmp_username = tc_select_value("empfullname", "employees", WHERE_EMPFULLNAME, $post_username);
        if (!isset($tmp_username)) {
            echo htmlspecialchars("$tmp_username, $post_username. Something is fishy here.\n");
            exit;
        }
    } else {
        $tmp_username = "";
    }

    $string = strstr($display_name, "\"");
    if (
        (!preg_match('/' . "^([[:alnum:]]| |-|'|,)+$" . '/i', $display_name)) || (empty($display_name)) || (empty($email_addy)) || (empty($office_name)) || (empty($group_name)) ||
        (!preg_match('/' . "^([[:alnum:]]|_|\.|-)+@([[:alnum:]]|\.|-)+(\.)([a-z]{2,4})$" . '/i', $email_addy)) || (($admin_perms != '1') && (!empty($admin_perms))) ||
        (($reports_perms != '1') && (!empty($reports_perms))) || (($time_admin_perms != '1') && (!empty($time_admin_perms))) || (($post_disabled != '1') &&
                                                                                                                                 (!empty($post_disabled))) || (!empty($string))
    ) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"row\">\n";
        $admin_leftnav_user_context = array('username' => $tmp_username, 'officename' => $get_office, 'current' => 'useredit.php');
        include_once 'leftnav_bootstrap.php';
        echo "    <div class=\"col-md-9\">\n";

        // begin post validation //

        if (empty($display_name)) {
            echo "      <div class=\"alert alert-danger\">A Display Name is required.</div>\n";
        } elseif (empty($email_addy)) {
            echo "      <div class=\"alert alert-danger\">An Email Address is required.</div>\n";
        } elseif (empty($office_name)) {
            echo "      <div class=\"alert alert-danger\">An Office is required.</div>\n";
        } elseif (empty($group_name)) {
            echo "      <div class=\"alert alert-danger\">A Group is required.</div>\n";
        } elseif (!empty($string)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed when creating an Username.</div>\n";
        } elseif (!preg_match('/' . "^([[:alnum:]]| |-|'|,)+$" . '/i', $display_name)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, apostrophes, commas, and spaces are allowed
                    when creating a Display Name.</div>\n";
        } elseif (!preg_match('/' . "^([[:alnum:]]|_|\.|-)+@([[:alnum:]]|\.|-)+(\.)([a-z]{2,4})$" . '/i', $email_addy)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, underscores, periods, and hyphens are allowed
                    when creating an Email Address.</div>\n";
        } elseif (($admin_perms != '1') && (!empty($admin_perms))) {
            echo "      <div class=\"alert alert-danger\">Choose \"yes\" or \"no\" for Sys Admin Perms.</div>\n";
        } elseif (($reports_perms != '1') && (!empty($reports_perms))) {
            echo "      <div class=\"alert alert-danger\">Choose \"yes\" or \"no\" for Reports Perms.</div>\n";
        } elseif (($time_admin_perms != '1') && (!empty($time_admin_perms))) {
            echo "      <div class=\"alert alert-danger\">Choose \"yes\" or \"no\" for Time Admin Perms.</div>\n";
        } elseif (($post_disabled != '1') && (!empty($post_disabled))) {
            echo "      <div class=\"alert alert-danger\">Choose \"yes\" or \"no\" for User Account Disabled.</div>\n";
        }

        if (
            !empty($office_name)
            and is_null(tc_select_value("officename", "offices", "officename = ?", $office_name))
        ) {
            echo "Office is not defined.\n";
            exit;
        }

        if (
            !empty($group_name)
            and is_null(tc_select_value("groupname", "groups", "groupname = ?", $group_name))
        ) {
            echo "Group is not defined.\n";
            exit;
        }

        // end post validation //

        $h_post_username = htmlentities($post_username);
        $h_display_name = htmlentities($display_name);
        $h_email_addy = htmlentities($email_addy);
        $h_user_barcode = htmlentities($user_barcode ?? '');

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Username</label>\n";
        echo "          <input type='hidden' name='post_username' value=\"$h_post_username\">\n";
        echo "          <div class=\"form-control-plaintext\">" . htmlentities($tmp_username) . "</div>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Display Name <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' class=\"form-control\" maxlength='50' name='display_name' value=\"$h_display_name\">\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Email Address <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' class=\"form-control\" maxlength='75' name='email_addy' value='$h_email_addy'>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Barcode</label>\n";
        echo "          <input type='text' class=\"form-control\" maxlength='75' name='barcode' value='$h_user_barcode'>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Office <span class=\"text-danger\">*</span></label>\n";
        echo "          <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
        echo "          </select>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Group <span class=\"text-danger\">*</span></label>\n";
        echo "          <select class=\"form-select\" name='group_name' onfocus='group_names();'>\n";
        echo "            <option selected>" . htmlentities($group_name) . "</option>\n";
        echo "          </select>\n";
        echo "        </div>\n";

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label d-block\">Sys Admin User?</label>\n";
        if (isset($evil)) {
            echo "          <div class=\"form-check form-check-inline\"><input disabled type='radio' class=\"form-check-input\" name='admin_perms' value='1'
                    checked id='admin_perms_y'><label class=\"form-check-label\" for='admin_perms_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input disabled type='radio' class=\"form-check-input\" name='admin_perms' value='0'
                    id='admin_perms_n'><label class=\"form-check-label\" for='admin_perms_n'>No</label></div>\n";
        } elseif ($admin_perms == "1") {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='admin_perms' value='1'
                    checked id='admin_perms_y'><label class=\"form-check-label\" for='admin_perms_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='admin_perms' value='0'
                    id='admin_perms_n'><label class=\"form-check-label\" for='admin_perms_n'>No</label></div>\n";
        } else {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='admin_perms' value='1'
                    id='admin_perms_y'><label class=\"form-check-label\" for='admin_perms_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='admin_perms' value='0'
                    checked id='admin_perms_n'><label class=\"form-check-label\" for='admin_perms_n'>No</label></div>\n";
        }
        echo "        </div>\n";

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label d-block\">Time Admin User?</label>\n";
        if ($time_admin_perms == "1") {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='1'
                    checked id='time_admin_perms_y'><label class=\"form-check-label\" for='time_admin_perms_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='0'
                    id='time_admin_perms_n'><label class=\"form-check-label\" for='time_admin_perms_n'>No</label></div>\n";
        } else {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='1'
                    id='time_admin_perms_y'><label class=\"form-check-label\" for='time_admin_perms_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='0'
                    checked id='time_admin_perms_n'><label class=\"form-check-label\" for='time_admin_perms_n'>No</label></div>\n";
        }
        echo "        </div>\n";

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label d-block\">Reports User?</label>\n";
        if ($reports_perms == "1") {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='1'
                    checked id='reports_perms_y'><label class=\"form-check-label\" for='reports_perms_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='0'
                    id='reports_perms_n'><label class=\"form-check-label\" for='reports_perms_n'>No</label></div>\n";
        } else {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='1'
                    id='reports_perms_y'><label class=\"form-check-label\" for='reports_perms_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='0'
                    checked id='reports_perms_n'><label class=\"form-check-label\" for='reports_perms_n'>No</label></div>\n";
        }
        echo "        </div>\n";

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label d-block\">User Account Disabled?</label>\n";
        if ($post_disabled == "1") {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='1'
                    checked id='disabled_y'><label class=\"form-check-label\" for='disabled_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='0'
                    id='disabled_n'><label class=\"form-check-label\" for='disabled_n'>No</label></div>\n";
        } else {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='1'
                    id='disabled_y'><label class=\"form-check-label\" for='disabled_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='0'
                    checked id='disabled_n'><label class=\"form-check-label\" for='disabled_n'>No</label></div>\n";
        }
        echo "        </div>\n";

        echo "        <p class=\"small text-muted\">* required</p>\n";
        if (isset($evil)) {
            echo "        <input type='hidden' name='evil' value='$evil'>\n";
        }
        echo "        <input type='hidden' name='get_office' value='$get_office'>\n";
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit User'>Edit User</button>\n";
        echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    tc_update_strings("employees", array(
        'displayname' => $display_name,
        'email'       => $email_addy,
        'barcode'     => $user_barcode,
        'groups'      => $group_name,
        'office'      => $office_name,
        'admin'       => $admin_perms,
        'reports'     => $reports_perms,
        'time_admin'  => $time_admin_perms,
        'disabled'    => $post_disabled
    ), WHERE_EMPFULLNAME, $post_username);

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_user_context = array('username' => $tmp_username, 'officename' => $office_name, 'current' => 'useredit.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <div class=\"alert alert-success\">User properties updated successfully.</div>\n";
    echo "      <h5><img src='../images/icons/user_edit.png'> Edit User</h5>\n";

    $result4 = tc_select(
        "empfullname, displayname, email, barcode, `groups`, office, admin, reports, time_admin, disabled",
        "employees",
        "empfullname = ? ORDER BY empfullname",
        $post_username
    );
    while ($row = mysqli_fetch_array($result4)) {
        $username = htmlentities("" . $row['empfullname'] . "");
        $displayname = htmlentities("" . $row['displayname'] . "");
        $user_email = htmlentities("" . $row['email'] . "");
        $user_barcode = htmlentities("" . $row['barcode'] . "");
        $office = htmlentities("" . $row['office'] . "");
        $groups = htmlentities("" . $row['groups'] . "");
        $admin = "" . $row['admin'] . "";
        $reports = "" . $row['reports'] . "";
        $time_admin = "" . $row['time_admin'] . "";
        $disabled = "" . $row['disabled'] . "";
    }
    ((mysqli_free_result($result4) || (is_object($result4) && (get_class($result4) == "mysqli_result"))) ? true : false);

    echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
    echo "        <tr><th>Username:</th><td>$username</td></tr>\n";
    echo "        <tr><th>Display Name:</th><td>$displayname</td></tr>\n";
    echo "        <tr><th>Email Address:</th><td>$user_email</td></tr>\n";
    echo "        <tr><th>Barcode:</th><td>$user_barcode</td></tr>\n";
    echo "        <tr><th>Office:</th><td>$office</td></tr>\n";
    echo "        <tr><th>Group:</th><td>$groups</td></tr>\n";

    $admin = ($admin == "1") ? "Yes" : "No";
    echo "        <tr><th>Sys Admin User?</th><td>$admin</td></tr>\n";
    $time_admin = ($time_admin == "1") ? "Yes" : "No";
    echo "        <tr><th>Time Admin User?</th><td>$time_admin</td></tr>\n";
    $reports = ($reports == "1") ? "Yes" : "No";
    echo "        <tr><th>Reports User?</th><td>$reports</td></tr>\n";
    $disabled = ($disabled == "1") ? "Yes" : "No";
    echo "        <tr><th>User Account Disabled?</th><td>$disabled</td></tr>\n";
    echo "      </table>\n";
    echo "      <a href='useradmin.php' class=\"btn btn-primary\">Done</a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
}
