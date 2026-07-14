<?php

require_once '../lib/session.php';
start_secure_session();

include '../config.inc.php';
include 'header_bootstrap.php';
include 'topmain_bootstrap.php';
echo "<title>$title - User Summary</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

const SELECT_EMPFULLNAME_FROM = "select empfullname from ";

require_once '../lib/auth.php';
require_valid_user();

echo "<div class=\"container-fluid mt-3\">\n";
echo "  <div class=\"row\">\n";
$admin_leftnav_current = 'useradmin.php';
include_once 'leftnav_bootstrap.php';
echo "    <div class=\"col-md-9\">\n";

$user_count = tc_query(SELECT_EMPFULLNAME_FROM . $db_prefix . "employees order by empfullname");
@$user_count_rows = mysqli_num_rows($user_count);

$admin_count = tc_query(SELECT_EMPFULLNAME_FROM . $db_prefix . "employees where admin = '1'");
@$admin_count_rows = mysqli_num_rows($admin_count);

$time_admin_count = tc_query(SELECT_EMPFULLNAME_FROM . $db_prefix . "employees where time_admin = '1'");
@$time_admin_count_rows = mysqli_num_rows($time_admin_count);

$reports_count = tc_query(SELECT_EMPFULLNAME_FROM . $db_prefix . "employees where reports = '1'");
@$reports_count_rows = mysqli_num_rows($reports_count);

echo "      <h5>User Summary</h5>\n";
echo "      <p class=\"small text-muted\">\n";
echo "        <img src='../images/icons/user_green.png'> Total Users: $user_count_rows &nbsp;\n";
echo "        <img src='../images/icons/user_orange.png'> Sys Admin Users: $admin_count_rows &nbsp;\n";
echo "        <img src='../images/icons/user_red.png'> Time Admin Users: $time_admin_count_rows &nbsp;\n";
echo "        <img src='../images/icons/user_suit.png'> Reports Users: $reports_count_rows\n";
echo "      </p>\n";
echo "      <div class=\"table-responsive\">\n";
echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
echo "        <tr>\n";
echo "          <th>&nbsp;</th>\n";
echo "          <th>Username</th>\n";
echo "          <th>Display Name</th>\n";
echo "          <th>Office</th>\n";
echo "          <th>Group</th>\n";
echo "          <th class=\"text-center\">Disabled</th>\n";
echo "          <th class=\"text-center\">Sys Admin</th>\n";
echo "          <th class=\"text-center\">Time Admin</th>\n";
echo "          <th class=\"text-center\">Reports</th>\n";
echo "          <th class=\"text-center\">Edit</th>\n";
echo "          <th class=\"text-center\">Chg Pwd</th>\n";
echo "          <th class=\"text-center\">Schedule</th>\n";
echo "          <th class=\"text-center\">Delete</th>\n";
echo "        </tr>\n";

$row_count = 0;

$query = "select empfullname, displayname, email, `groups`, office, admin, reports, time_admin, disabled from " . $db_prefix . "employees
          order by empfullname";
$result = tc_query($query);

while ($row = mysqli_fetch_array($result)) {
    $empfullname = stripslashes("" . $row['empfullname'] . "");
    $displayname = stripslashes("" . $row['displayname'] . "");

    $row_count++;
    $row_color = ($row_count % 2) ? $color2 : $color1;

    $h_empfullname = htmlentities($empfullname);
    $h_office = htmlentities($row['office']);
    $officename_qs = urlencode($row['office']);

    echo "        <tr style=\"background-color:$row_color;\">\n";
    echo "          <td>$row_count</td>\n";
    echo "          <td><a title=\"Edit User: $h_empfullname\"
                    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">$h_empfullname</a></td>\n";
    echo "          <td>" . htmlentities($displayname) . "</td>\n";
    echo "          <td>$h_office</td>\n";
    echo "          <td>" . htmlentities($row['groups']) . "</td>\n";

    if ("" . $row["disabled"] . "" == 1) {
        echo "          <td class=\"text-center\"><img src='../images/icons/cross.png'/></td>\n";
    } else {
        echo "          <td class=\"text-center\"></td>\n";
    }
    if ("" . $row["admin"] . "" == 1) {
        echo "          <td class=\"text-center\"><img src='../images/icons/accept.png'/></td>\n";
    } else {
        echo "          <td class=\"text-center\"></td>\n";
    }
    if ("" . $row["time_admin"] . "" == 1) {
        echo "          <td class=\"text-center\"><img src='../images/icons/accept.png'/></td>\n";
    } else {
        echo "          <td class=\"text-center\"></td>\n";
    }
    if ("" . $row["reports"] . "" == 1) {
        echo "          <td class=\"text-center\"><img src='../images/icons/accept.png'/></td>\n";
    } else {
        echo "          <td class=\"text-center\"></td>\n";
    }

    if ((strpos($user_agent, "MSIE 6")) || (strpos($user_agent, "MSIE 5")) || (strpos($user_agent, "MSIE 4")) || (strpos($user_agent, "MSIE 3"))) {
        echo "          <td class=\"text-center\"><a title=\"Edit User: $h_empfullname\"
    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">Edit</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Change Password: $h_empfullname\"
    href=\"chngpasswd.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">Chg Pwd</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Schedule: $h_empfullname\"
    href=\"scheduleedit.php?username=" . urlencode($empfullname) . "\">Schedule</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete User: $h_empfullname\"
    href=\"userdelete.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">Delete</a></td></tr>\n";
    } else {
        echo "          <td class=\"text-center\"><a title=\"Edit User: $h_empfullname\"
    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
    <img border=0 src='../images/icons/application_edit.png'/></a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Change Password: $h_empfullname\"
    href=\"chngpasswd.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\"><img border=0
                                                                                     src='../images/icons/lock_edit.png'/></a>
</td>\n";
        echo "          <td class=\"text-center\"><a title=\"Schedule: $h_empfullname\"
    href=\"scheduleedit.php?username=" . urlencode($empfullname) . "\"><img border=0
                                                          src='../images/icons/clock.png'/></a>
</td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete User: $h_empfullname\"
    href=\"userdelete.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
    <img border=0 src='../images/icons/delete.png'/></a></td></tr>\n";
    }
}
echo "      </table>\n";
echo "      </div>\n";
echo "    </div>\n";
echo "  </div>\n";
echo "</div>\n";
include_once 'footer_bootstrap.php';
exit;
