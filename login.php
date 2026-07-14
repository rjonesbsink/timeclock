<?php

require_once 'lib/session.php';
start_secure_session();

include 'config.inc.php';
include 'header_bootstrap.php';
include 'topmain_bootstrap.php';
require_once 'lib/csrf.php';
echo "<title>$title - Admin Login</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);

if (isset($_POST['login_userid']) && (isset($_POST['login_password'])) && verify_csrf_token()) {
    // Guard against an array payload: it otherwise flows unguarded into a
    // tc_select() bind param and crypt()/password_verify() via
    // tc_verify_password(), which is a fatal TypeError/ArgumentCountError
    // under PHP 8.
    $login_userid = post_string('login_userid');
    $login_password = post_string('login_password');

    $result = tc_select("empfullname, employee_passwd, admin, time_admin", "employees", "empfullname = ?", $login_userid);

    while ($row = mysqli_fetch_array($result)) {
        $admin_username = "" . $row['empfullname'] . "";
        $admin_password = "" . $row['employee_passwd'] . "";
        $admin_auth = "" . $row['admin'] . "";
        $time_admin_auth = "" . $row['time_admin'] . "";
    }

    $password_ok = isset($admin_password) && tc_verify_password($login_password, $admin_password);

    if (($login_userid == @$admin_username) && $password_ok && ($admin_auth == "1")) {
        $_SESSION['valid_user'] = $login_userid;
        tc_maybe_upgrade_password($admin_username, $login_password, $admin_password);
        regenerate_csrf_token();
    } elseif (($login_userid == @$admin_username) && $password_ok && ($time_admin_auth == "1")) {
        $_SESSION['time_admin_valid_user'] = $login_userid;
        tc_maybe_upgrade_password($admin_username, $login_password, $admin_password);
        regenerate_csrf_token();
    }
}

if (isset($_SESSION['valid_user'])) {
    echo "<script type='text/javascript' language='javascript'> window.location.href = 'admin/index.php';</script>";
    exit;
} elseif (isset($_SESSION['time_admin_valid_user'])) {
    echo "<script type='text/javascript' language='javascript'> window.location.href = 'admin/timeadmin.php';</script>";
    exit;
} else {
    // build form

    echo "<div class=\"container\">\n";
    echo "  <div class=\"row justify-content-center\">\n";
    echo "    <div class=\"col-12 col-sm-8 col-md-5 col-lg-4 mt-5\">\n";
    echo "      <div class=\"card shadow-sm\">\n";
    echo "        <div class=\"card-body p-4\">\n";
    echo "          <h1 class=\"h5 card-title text-center mb-4\">PHP Timeclock Admin Login</h1>\n";
    echo "          <form name='auth' method='post' action='$self'>\n";
    echo csrf_field() . "\n";

    if (isset($login_userid)) {
        echo "            <div class=\"alert alert-danger py-2\" role=\"alert\">Could not log you in. Either your username or password is incorrect.</div>\n";
    }

    echo "            <div class=\"mb-3\">\n";
    echo "              <label for=\"login_userid\" class=\"form-label\">Username</label>\n";
    echo "              <input type='text' class=\"form-control\" id=\"login_userid\" name='login_userid'>\n";
    echo "            </div>\n";
    echo "            <div class=\"mb-3\">\n";
    echo "              <label for=\"login_password\" class=\"form-label\">Password</label>\n";
    echo "              <input type='password' class=\"form-control\" id=\"login_password\" name='login_password'>\n";
    echo "            </div>\n";
    echo "            <div class=\"d-grid\">\n";
    echo "              <input type='submit' class=\"btn btn-primary\" value='Log In'>\n";
    echo "            </div>\n";
    echo "          </form>\n";
    echo "        </div>\n";
    echo "      </div>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    echo "<script language=\"javascript\">document.forms['auth'].login_userid.focus();</script>\n";
}

include_once 'footer_bootstrap.php';
