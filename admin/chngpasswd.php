<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Change Password</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const WHERE_EMPFULLNAME = "empfullname = ?";
const FOOTER_PHP = 'footer_bootstrap.php';

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    if (!isset($_GET['username'])) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"alert alert-danger\">\n";
        echo "    <h5>PHP Timeclock Error!</h5>\n";
        echo "    <p>How did you get here?</p>\n";
        echo "    <p>Go back to the <a href='useradmin.php'>User Summary</a> page to change passwords.</p>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    $get_user = get_string('username');
    $get_office = get_string('officename');

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_user_context = array('username' => $get_user, 'officename' => $get_office, 'current' => 'chngpasswd.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $result = tc_select("empfullname", "employees", WHERE_EMPFULLNAME, $get_user);
    while ($row = mysqli_fetch_array($result)) {
        $username = "" . $row['empfullname'] . "";
    }
    if (!isset($username)) {
        echo "username is not defined for this user.\n";
        exit;
    }

    if (!empty($get_office)) {
        $result = tc_select("*", "offices", "officename = ?", $get_office);
        while ($row = mysqli_fetch_array($result)) {
            $getoffice = "" . $row['officename'] . "";
        }
    }
    if (!isset($getoffice)) {
        echo "Office is not defined for this user. Go back and associate this user with an office.\n";
        exit;
    }

    $h_username = htmlentities($username);
    $h_get_office = htmlentities($get_office);

    echo "      <h5><img src='../images/icons/lock_edit.png'> Change Password</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Username</label>\n";
    echo "          <input type='hidden' name='post_username' value=\"$h_username\">\n";
    echo "          <div class=\"form-control-plaintext\">$h_username</div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">New Password</label>\n";
    echo "          <input type='password' class=\"form-control\" maxlength='25' name='new_password'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Confirm Password</label>\n";
    echo "          <input type='password' class=\"form-control\" maxlength='25' name='confirm_password'>\n";
    echo "        </div>\n";
    echo "        <input type='hidden' name='get_office' value=\"$h_get_office\">\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Change Password'>Change Password</button>\n";
    echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $post_username = stripslashes(post_string('post_username'));
    $new_password = post_string('new_password');
    $confirm_password = post_string('confirm_password');
    $get_office = post_string('get_office');

    // begin post validation //

    if (!empty($get_office)) {
        $result = tc_select("*", "offices", "officename = ?", $get_office);
        while ($row = mysqli_fetch_array($result)) {
            $getoffice = "" . $row['officename'] . "";
        }
    }
    if (!isset($getoffice)) {
        echo "Office is not defined for this user. Go back and associate this user with an office.\n";
        exit;
    }

    // end post validation //

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_user_context = array('username' => $post_username, 'officename' => $get_office, 'current' => 'chngpasswd.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    // begin post validation //

    if (!empty($post_username)) {
        $result = tc_select("*", "employees", WHERE_EMPFULLNAME, $post_username);
        while ($row = mysqli_fetch_array($result)) {
            $username = "" . $row['empfullname'] . "";
        }
        if (!isset($username)) {
            echo "username is not defined for this user.\n";
            exit;
        }
    }

    if (preg_match("/^[\s\\/;'\"-]*$/i", $new_password)) {
        $evil_password = '1';
        echo "      <div class=\"alert alert-danger\">Single and double quotes, backward and forward slashes, semicolons, and spaces
                are not allowed when creating a Password.</div>\n";
    } elseif ($new_password !== $confirm_password) {
        $evil_password = '1';
        echo "      <div class=\"alert alert-danger\">Passwords do not match.</div>\n";
    }

    // end post validation //

    $h_post_username = htmlentities($post_username);
    $h_get_office = htmlentities($get_office);

    if (isset($evil_password)) {
        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Username</label>\n";
        echo "          <input type='hidden' name='post_username' value=\"$h_post_username\">\n";
        echo "          <div class=\"form-control-plaintext\">$h_post_username</div>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">New Password</label>\n";
        echo "          <input type='password' class=\"form-control\" maxlength='25' name='new_password'>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">Confirm Password</label>\n";
        echo "          <input type='password' class=\"form-control\" maxlength='25' name='confirm_password'>\n";
        echo "        </div>\n";
        echo "        <input type='hidden' name='get_office' value=\"$h_get_office\">\n";
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Change Password'>Change Password</button>\n";
        echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    } else {
        $new_password = tc_hash_password($new_password);

        tc_update_strings("employees", array("employee_passwd" => $new_password), WHERE_EMPFULLNAME, $post_username);

        echo "      <div class=\"alert alert-success\">Password changed successfully.</div>\n";
        echo "      <h5><img src='../images/icons/lock_edit.png'> Change Password</h5>\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Username:</th><td>$h_post_username</td></tr>\n";
        echo "        <tr><th>New Password</th><td>***hidden***</td></tr>\n";
        echo "      </table>\n";
        echo "      <a href='useradmin.php' class=\"btn btn-primary\">Done</a>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }
}
