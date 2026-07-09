<?php
session_start();

include 'config.inc.php';
include 'header.php';
include 'topmain.php';
require_once 'lib/csrf.php';
echo "<title>$title - Admin Login</title>\n";

$self = $_SERVER['PHP_SELF'];

if (isset($_POST['login_userid']) && (isset($_POST['login_password'])) && verify_csrf_token()) {
    $login_userid = $_POST['login_userid'];
    $login_password = $_POST['login_password'];

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

    echo "<form name='auth' method='post' action='$self'>\n";
    echo csrf_field() . "\n";
    echo "<table align=center width=210 border=0 cellpadding=7 cellspacing=1>\n";
    echo "  <tr class=right_main_text><td colspan=2 height=35 align=center valign=top class=title_underline>PHP Timeclock Admin Login</td></tr>\n";
    echo "  <tr class=right_main_text><td align=left>Username:</td><td align=right><input type='text' name='login_userid'></td></tr>\n";
    echo "  <tr class=right_main_text><td align=left>Password:</td><td align=right><input type='password' name='login_password'></td></tr>\n";
    echo "  <tr class=right_main_text><td align=center colspan=2><input type='submit' onClick='admin.php' value='Log In'></td></tr>\n";

    if (isset($login_userid)) {
        echo "  <tr class=right_main_text><td align=center colspan=2>Could not log you in. Either your username or password is incorrect.</td></tr>\n";
    }

    echo "</table>\n";
    echo "</form>\n";
    echo "<script language=\"javascript\">document.forms['auth'].login_userid.focus();</script>\n";
}

echo "</body>\n";
echo "</html>\n";
?>
