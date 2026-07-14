<?php

require_once 'lib/session.php';
start_secure_session();

include 'config.inc.php';
include 'header.php';
include 'topmain.php';
require_once 'lib/csrf.php';
echo "<title>$title - Reports Login</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);

if (isset($_POST['login_userid']) && (isset($_POST['login_password'])) && verify_csrf_token()) {
    // Guard against an array payload -- see login.php.
    $login_userid = post_string('login_userid');
    $login_password = post_string('login_password');

    $result = tc_select("empfullname, employee_passwd, reports", "employees", "empfullname = ?", $login_userid);

    while ($row = mysqli_fetch_array($result)) {
        $reports_username = "" . $row['empfullname'] . "";
        $reports_password = "" . $row['employee_passwd'] . "";
        $reports_auth = "" . $row['reports'] . "";
    }

    $password_ok = isset($reports_password) && tc_verify_password($login_password, $reports_password);

    if (($login_userid == @$reports_username) && $password_ok && ($reports_auth == "1")) {
        $_SESSION['valid_reports_user'] = $login_userid;
        tc_maybe_upgrade_password($reports_username, $login_password, $reports_password);
        regenerate_csrf_token();
    }
}

if (isset($_SESSION['valid_reports_user'])) {
    echo "<script type='text/javascript' language='javascript'> window.location.href = 'reports/index.php';</script>";
    exit;
} else {
    // build form

    echo "<form name='auth' method='post' action='$self'>\n";
    echo csrf_field() . "\n";
    echo "<table align=center width=210 border=0 cellpadding=7 cellspacing=1>\n";
    echo "  <tr class=right_main_text><td colspan=2 height=35 align=center valign=top class=title_underline>PHP Timeclock Reports Login</td></tr>\n";
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
