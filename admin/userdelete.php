<?php

require_once '../lib/session.php';
start_secure_session();

include '../config.inc.php';
include 'header_bootstrap.php';
include 'topmain_bootstrap.php';
echo "<title>$title - Delete User</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const MSG_SOMETHING_FISHY = "Something is fishy here.\n";
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
        echo "    <p>Go back to the <a href='useradmin.php'>User Summary</a> page to delete users.</p>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    $get_user = htmlentities(get_string('username'));

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_user_context = array('username' => get_string('username'), 'officename' => get_string('officename'), 'current' => 'userdelete.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $result = tc_select("*", "employees", "empfullname = ? ORDER BY empfullname", $get_user);

    while ($row = mysqli_fetch_array($result)) {
        $username = htmlentities("" . $row['empfullname'] . "");
        $displayname = htmlentities("" . $row['displayname'] . "");
        $user_email = htmlentities("" . $row['email'] . "");
        $user_barcode = htmlentities("" . $row['barcode'] . "");
        $office = htmlentities("" . $row['office'] . "");
        $groups = htmlentities("" . $row['groups'] . "");
        $admin = "" . $row['admin'] . "";
        $reports = "" . $row['reports'] . "";
        $time_admin = "" . $row['time_admin'] . "";
    }
    ((mysqli_free_result($result) || (is_object($result) && (get_class($result) == "mysqli_result"))) ? true : false);

    // make sure you cannot delete the last admin user in the system!! //

    if (!empty($admin)) {
        @$admin_count_rows = mysqli_num_rows(tc_select("empfullname", "employees", "admin = '1'"));
        if (@$admin_count_rows == "1") {
            $evil = "1";
        }
    }

    echo "      <h5><img src='../images/icons/user_delete.png'> Delete User</h5>\n";

    if (isset($evil)) {
        echo "      <div class=\"alert alert-danger\">Cannot delete this user. This user is the last Sys Admin User in the system. Go back
                and give another user Sys Admin privileges before attempting to delete this user again.</div>\n";
    }

    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
    echo "        <tr><th>Username:</th><td><input type='hidden' name='post_username' value=\"$username\">$username</td></tr>\n";
    echo "        <tr><th>Display Name:</th><td><input type='hidden' name='display_name' value=\"$displayname\">$displayname</td></tr>\n";
    echo "        <tr><th>Email Address:</th><td><input type='hidden' name='email_addy' value=\"$user_email\">$user_email</td></tr>\n";
    echo "        <tr><th>Barcode:</th><td><input type='hidden' name='barcode' value=\"$user_barcode\">$user_barcode</td></tr>\n";
    echo "        <tr><th>Office:</th><td><input type='hidden' name='office_name' value=\"$office\">$office</td></tr>\n";
    echo "        <tr><th>Group:</th><td><input type='hidden' name='group_name' value=\"$groups\">$groups</td></tr>\n";

    $admin_yes_no = ($admin == "1") ? "Yes" : "No";
    echo "        <tr><th>Sys Admin:</th><td><input type='hidden' name='admin_perms' value='$admin'>$admin_yes_no</td></tr>\n";
    $time_admin_yes_no = ($time_admin == "1") ? "Yes" : "No";
    echo "        <tr><th>Time Admin:</th><td><input type='hidden' name='time_admin_perms' value='$time_admin'>$time_admin_yes_no</td></tr>\n";
    $reports_yes_no = ($reports == "1") ? "Yes" : "No";
    echo "        <tr><th>Reports:</th><td><input type='hidden' name='reports_perms' value='$reports'>$reports_yes_no</td></tr>\n";
    echo "      </table>\n";

    $delete_data_style = isset($evil) ? " style=\"display:none;\"" : "";
    echo "      <div class=\"form-check mb-3\"$delete_data_style>\n";
    echo "        <input type='checkbox' class=\"form-check-input\" id='delete_all_user_data' name='delete_all_user_data' value='1'>\n";
    echo "        <label class=\"form-check-label\" for='delete_all_user_data'>Delete all punch-in/out history for this user?</label>\n";
    echo "      </div>\n";

    if (!isset($evil)) {
        echo "      <button type='submit' class=\"btn btn-danger\" name='submit' value='Delete User'>Delete User</button>\n";
        echo "      <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    } else {
        echo "      <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    }
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $post_username = post_string('post_username');
    $display_name = post_string('display_name');
    $email_addy = post_string('email_addy');
    $user_barcode = post_string('barcode');
    $office_name = post_string('office_name');
    $group_name = post_string('group_name');
    $admin_perms = post_string('admin_perms');
    $reports_perms = post_string('reports_perms');
    $time_admin_perms = post_string('time_admin_perms');
    @$delete_data = $_POST['delete_all_user_data'];

    // begin post validation //

    if (
        !empty($post_username)
         and is_null(tc_select_value("empfullname", "employees", "empfullname = ?", $post_username))
    ) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }

    if (
        !empty($display_name)
         and is_null(tc_select_value("displayname", "employees", "empfullname = ? AND displayname = ?", array($post_username, $display_name)))
    ) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }

    if (
        !empty($email_addy)
         and is_null(tc_select_value("email", "employees", "empfullname = ? AND email = ?", array($post_username, $email_addy)))
    ) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }

    if (
        !empty($office_name)
         and is_null(tc_select_value("office", "employees", "empfullname = ? AND office = ?", array($post_username, $office_name)))
    ) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }

    if (
        !empty($group_name)
         and is_null(tc_select_value("`groups`", "employees", "empfullname = ? AND `groups` = ?", array($post_username, $group_name)))
    ) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }

    if (($admin_perms != '0') && ($admin_perms != '1')) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }
    if (($reports_perms != '0') && ($reports_perms != '1')) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }
    if (($time_admin_perms != '0') && ($time_admin_perms != '1')) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }
    if ((isset($delete_data)) && ($delete_data != '1')) {
        echo MSG_SOMETHING_FISHY;
        exit;
    }

    // end post validation //

    tc_delete("employees", "empfullname = ?", $post_username);

    if ($delete_data == "1") {
        tc_delete("info", "fullname = ?", $post_username);
    }

    $post_username = htmlentities($post_username);
    $display_name = htmlentities($display_name);
    $email_addy = htmlentities($email_addy);
    $office_name = htmlentities($office_name);
    $group_name = htmlentities($group_name);

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'useradmin.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <div class=\"alert alert-success\">User deleted successfully.</div>\n";
    echo "      <h5><img src='../images/icons/user_delete.png'> Delete User</h5>\n";
    echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
    echo "        <tr><th>Username:</th><td>$post_username</td></tr>\n";
    echo "        <tr><th>Display Name:</th><td>$display_name</td></tr>\n";
    echo "        <tr><th>Email Address:</th><td>$email_addy</td></tr>\n";
    echo "        <tr><th>Office:</th><td>$office_name</td></tr>\n";
    echo "        <tr><th>Group:</th><td>$group_name</td></tr>\n";

    $admin_yes_no = ($admin_perms == "1") ? "Yes" : "No";
    echo "        <tr><th>Sys Admin:</th><td>$admin_yes_no</td></tr>\n";
    $time_admin_yes_no = ($time_admin_perms == "1") ? "Yes" : "No";
    echo "        <tr><th>Time Admin:</th><td>$time_admin_yes_no</td></tr>\n";
    $reports_yes_no = ($reports_perms == "1") ? "Yes" : "No";
    echo "        <tr><th>Reports:</th><td>$reports_yes_no</td></tr>\n";
    echo "      </table>\n";
    echo "      <a href='useradmin.php' class=\"btn btn-primary\">Done</a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
}
