<?php

require_once '../lib/session.php';
start_secure_session();

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
const FOOTER_PHP = 'footer_bootstrap.php';
const USERNAME_PATTERN = "^([[:alnum:]]| |-|'|,)+$";

include_once '../config.inc.php';
if ($request !== 'POST') {
    include_once 'header_get_bootstrap.php';
    include_once 'topmain_bootstrap.php';
}
echo "<title>$title - Create User</title>\n";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'usercreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <h5><img src='../images/icons/user_add.png'> Create User</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Username <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='50' name='post_username'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Display Name <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='50' name='display_name'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Password</label>\n";
    echo "          <input type='password' class=\"form-control\" maxlength='25' name='password'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Confirm Password</label>\n";
    echo "          <input type='password' class=\"form-control\" maxlength='25' name='confirm_password'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Email Address <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='75' name='email_addy'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Barcode</label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='75' name='barcode'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Office <span class=\"text-danger\">*</span></label>\n";
    echo "          <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Group <span class=\"text-danger\">*</span></label>\n";
    echo "          <select class=\"form-select\" name='group_name'>\n";
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Sys Admin User?</label>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='admin_perms' value='1' id='admin_perms_y'>
                    <label class=\"form-check-label\" for='admin_perms_y'>Yes</label></div>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='admin_perms' value='0' checked id='admin_perms_n'>
                    <label class=\"form-check-label\" for='admin_perms_n'>No</label></div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Time Admin User?</label>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='1' id='time_admin_perms_y'>
                    <label class=\"form-check-label\" for='time_admin_perms_y'>Yes</label></div>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='time_admin_perms' value='0' checked id='time_admin_perms_n'>
                    <label class=\"form-check-label\" for='time_admin_perms_n'>No</label></div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Reports User?</label>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='1' id='reports_perms_y'>
                    <label class=\"form-check-label\" for='reports_perms_y'>Yes</label></div>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='reports_perms' value='0' checked id='reports_perms_n'>
                    <label class=\"form-check-label\" for='reports_perms_n'>No</label></div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">User Account Disabled?</label>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='1' id='disabled_y'>
                    <label class=\"form-check-label\" for='disabled_y'>Yes</label></div>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='disabled' value='0' checked id='disabled_n'>
                    <label class=\"form-check-label\" for='disabled_n'>No</label></div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Initial Punch</label>\n";
    echo "          <select class=\"form-select\" name='inout'><option value=''>...</option>" . html_options(tc_select("punchitems", "punchlist")) . "</select>\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create User'>Create User</button>\n";
    echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
} elseif ($request == 'POST') {
    include_once 'header_post_bootstrap.php';
    include_once 'topmain_bootstrap.php';

    require_csrf_token();

    $post_username = post_string('post_username');
    $display_name = post_string('display_name');
    $password = post_string('password');
    $confirm_password = post_string('confirm_password');
    $email_addy = post_string('email_addy');
    $user_barcode = value_or_null(post_string('barcode'));// UNIQUE constraint so no empty strings
    $office_name = post_string('office_name');
    $group_name = post_string('group_name');
    $admin_perms = post_string('admin_perms');
    $reports_perms = post_string('reports_perms');
    $time_admin_perms = post_string('time_admin_perms');
    $post_disabled = post_string('disabled');
    $inout = post_string('inout');

    $username_exists = entity_name_exists("employees", "empfullname", $post_username);

    $string = strstr($post_username, "\"");
    $string2 = strstr($display_name, "\"");

    if (
        ($username_exists) || ($password !== $confirm_password) ||
        (!preg_match('/' . USERNAME_PATTERN . '/i', $post_username)) || (!preg_match('/' . USERNAME_PATTERN . '/i', $display_name)) || (empty($post_username)) ||
        (empty($display_name)) || (empty($email_addy)) || (empty($office_name)) || (empty($group_name)) ||
        (!preg_match('/' . "^([[:alnum:]]|~|\!|@|#|\$|%|\^|&|\*|\(|\)|-|\+|`|_|\=|[{]|[}]|\[|\]|\||\:|\<|\>|\.|,|\?)+$" . '/i', $password)) ||
        (!preg_match('/' . "^([[:alnum:]]|_|\.|-)+@([[:alnum:]]|\.|-)+(\.)([a-z]{2,4})$" . '/i', $email_addy)) || (($admin_perms != '1') && (!empty($admin_perms))) ||
        (($reports_perms != '1') && (!empty($reports_perms))) || (($time_admin_perms != '1') && (!empty($time_admin_perms))) ||
        (($post_disabled != '1') && (!empty($post_disabled))) || (!empty($string)) || (!empty($string2))
    ) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"row\">\n";
        $admin_leftnav_current = 'usercreate.php';
        include_once 'leftnav_bootstrap.php';
        echo "    <div class=\"col-md-9\">\n";

        $h_post_username = htmlentities($post_username);
        $h_display_name = htmlentities($display_name);
        $h_email_addy = htmlentities($email_addy);
        $h_user_barcode = htmlentities($user_barcode ?? '');

        // begin post validation //

        if (empty($post_username)) {
            echo "      <div class=\"alert alert-danger\">A Username is required.</div>\n";
        } elseif (empty($display_name)) {
            echo "      <div class=\"alert alert-danger\">A Display Name is required.</div>\n";
        } elseif (!empty($string)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed when creating an Username.</div>\n";
        } elseif (!empty($string2)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed when creating an Display Name.</div>\n";
        } elseif (empty($email_addy)) {
            echo "      <div class=\"alert alert-danger\">An Email Address is required.</div>\n";
        } elseif (empty($office_name)) {
            echo "      <div class=\"alert alert-danger\">An Office is required.</div>\n";
        } elseif (empty($group_name)) {
            echo "      <div class=\"alert alert-danger\">A Group is required.</div>\n";
        } elseif ($username_exists) {
            echo "      <div class=\"alert alert-danger\">User already exists. Create another username.</div>\n";
        } elseif (!preg_match('/' . USERNAME_PATTERN . '/i', $post_username)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, apostrophes, commas, and spaces are allowed
                    when creating a Username.</div>\n";
        } elseif (!preg_match('/' . USERNAME_PATTERN . '/i', $display_name)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, apostrophes, commas, and spaces are allowed
                    when creating a Display Name.</div>\n";
        } elseif (!preg_match('/' . "^([[:alnum:]]|~|\!|@|#|\$|%|\^|&|\*|\(|\)|-|\+|`|_|\=|[{]|[}]|\[|\]|\||\:|\<|\>|\.|,|\?)+$" . '/i', $password)) {
            echo "      <div class=\"alert alert-danger\">Single and double quotes, backward and forward slashes, semicolons, and spaces
                    are not allowed when creating a Password.</div>\n";
        } elseif ($password != $confirm_password) {
            echo "      <div class=\"alert alert-danger\">Passwords do not match.</div>\n";
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

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Username <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' class=\"form-control\" maxlength='50' name='post_username' value=\"$h_post_username\">\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Display Name <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' class=\"form-control\" maxlength='50' name='display_name' value=\"$h_display_name\">\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Password</label>\n";
        echo "          <input type='password' class=\"form-control\" maxlength='25' name='password'>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Confirm Password</label>\n";
        echo "          <input type='password' class=\"form-control\" maxlength='25' name='confirm_password'>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Email Address <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' class=\"form-control\" maxlength='75' name='email_addy' value=\"$h_email_addy\">\n";
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
        if ($admin_perms == "1") {
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

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Initial Punch</label>\n";
        echo "          <select class=\"form-select\" name='inout'><option value=''>...</option>"
            . html_options(tc_select("punchitems", "punchlist"), $inout) . "</select>\n";
        echo "        </div>\n";
        echo "        <p class=\"small text-muted\">* required</p>\n";
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create User'>Create User</button>\n";
        echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    $password = tc_hash_password($password);

    tc_insert_strings("employees", array(
        'empfullname'     => $post_username,
        'displayname'     => $display_name,
        'employee_passwd' => $password,
        'email'           => $email_addy,
        'barcode'         => $user_barcode,
        'groups'          => $group_name,
        'office'          => $office_name,
        'admin'           => $admin_perms,
        'reports'         => $reports_perms,
        'time_admin'      => $time_admin_perms,
        'disabled'        => $post_disabled
    ));

    if (has_value($inout)) {
        $inout = tc_select_value("punchitems", "punchlist", "punchitems = ?", $inout);
        if (has_value($inout)) {
            $tz_stamp = time();
            $clockin = array("fullname" => $post_username, "inout" => $inout, "timestamp" => $tz_stamp);
            if (yes_no_bool($ip_logging)) {
                $clockin["ipaddress"] = $connecting_ip;
            }
            tc_insert_strings("info", $clockin);
            tc_update_strings("employees", array("tstamp" => $tz_stamp), "empfullname = ?", $post_username);
        }
    }

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'usercreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <div class=\"alert alert-success\">User created successfully.</div>\n";
    echo "      <h5><img src='../images/icons/user_add.png'> Create User</h5>\n";

    $result4 = tc_select(
        "empfullname, displayname, email, barcode, `groups`, office, admin, reports, time_admin, disabled",
        "employees",
        "empfullname = ? ORDER BY empfullname",
        $post_username
    );
    while ($row = mysqli_fetch_array($result4)) {
        $username = "" . $row['empfullname'] . "";
        $displayname = "" . $row['displayname'] . "";
        $user_email = "" . $row['email'] . "";
        $user_barcode = "" . $row['barcode'] . "";
        $office = "" . $row['office'] . "";
        $groups = "" . $row['groups'] . "";
        $admin = "" . $row['admin'] . "";
        $reports = "" . $row['reports'] . "";
        $time_admin = "" . $row['time_admin'] . "";
        $disabled = "" . $row['disabled'] . "";
    }
    ((mysqli_free_result($result4) || (is_object($result4) && (get_class($result4) == "mysqli_result"))) ? true : false);

    echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
    echo "        <tr><th>Username:</th><td>" . htmlentities($username) . "</td></tr>\n";
    echo "        <tr><th>Display Name:</th><td>" . htmlentities($displayname) . "</td></tr>\n";
    echo "        <tr><th>Password:</th><td>***hidden***</td></tr>\n";
    echo "        <tr><th>Email Address:</th><td>" . htmlentities($user_email) . "</td></tr>\n";
    echo "        <tr><th>Barcode:</th><td>" . htmlentities($user_barcode) . "</td></tr>\n";
    echo "        <tr><th>Office:</th><td>" . htmlentities($office) . "</td></tr>\n";
    echo "        <tr><th>Group:</th><td>" . htmlentities($groups) . "</td></tr>\n";

    $admin = ($admin == "1") ? "Yes" : "No";
    echo "        <tr><th>Sys Admin User?</th><td>$admin</td></tr>\n";
    $time_admin = ($time_admin == "1") ? "Yes" : "No";
    echo "        <tr><th>Time Admin User?</th><td>$time_admin</td></tr>\n";
    $reports = ($reports == "1") ? "Yes" : "No";
    echo "        <tr><th>Reports User?</th><td>$reports</td></tr>\n";
    $disabled = ($disabled == "1") ? "Yes" : "No";
    echo "        <tr><th>User Account Disabled?</th><td>$disabled</td></tr>\n";
    echo "        <tr><th>Initial Punch:</th><td>" . htmlentities($inout) . "</td></tr>\n";
    echo "      </table>\n";
    echo "      <a href='usercreate.php' class=\"btn btn-primary\">Done</a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
}
