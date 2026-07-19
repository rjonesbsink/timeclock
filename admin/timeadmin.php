<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Add/Edit/Delete Time</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

require_once '../lib/auth.php';
require_time_admin();

echo "<div class=\"container-fluid mt-3\">\n";
echo "  <div class=\"row\">\n";
$admin_leftnav_current = 'timeadmin.php';
include_once 'leftnav_bootstrap.php';
echo "    <div class=\"col-md-9\">\n";

echo "      <h5>Add/Edit/Delete Time</h5>\n";
echo "      <div class=\"table-responsive\">\n";
echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
echo "        <tr>\n";
echo "          <th>&nbsp;</th>\n";
echo "          <th>Username</th>\n";
echo "          <th>Display Name</th>\n";
echo "          <th>Office</th>\n";
echo "          <th>Group</th>\n";
echo "          <th class=\"text-center\">Disabled</th>\n";
echo "          <th class=\"text-center\">Add</th>\n";
echo "          <th class=\"text-center\">Edit</th>\n";
echo "          <th class=\"text-center\">Delete</th>\n";
echo "        </tr>\n";

$row_count = 0;

$result = tc_query("select empfullname, displayname, email, `groups`, office, admin, reports, disabled from " . $db_prefix . "employees order by empfullname");

while ($row = mysqli_fetch_array($result)) {
    $empfullname = stripslashes("" . $row['empfullname'] . "");
    $displayname = stripslashes("" . $row['displayname'] . "");

    $row_count++;
    $row_color = ($row_count % 2) ? $color2 : $color1;

    $h_empfullname = htmlentities($empfullname);
    $username_qs = urlencode($empfullname);

    echo "        <tr style=\"background-color:$row_color;\">\n";
    echo "          <td>$row_count</td>\n";
    echo "          <td><a title=\"Edit Time For: $h_empfullname\" href=\"timeedit.php?username=$username_qs\">$h_empfullname</a></td>\n";
    echo "          <td>" . htmlentities($displayname) . "</td>\n";
    echo "          <td>" . htmlentities($row['office']) . "</td>\n";
    echo "          <td>" . htmlentities($row['groups']) . "</td>\n";

    if ("" . $row["disabled"] . "" == 1) {
        echo "          <td class=\"text-center\"><img src='../images/icons/cross.png'/></td>\n";
    } else {
        echo "          <td class=\"text-center\"></td>\n";
    }

    if ((strpos($user_agent, "MSIE 6")) || (strpos($user_agent, "MSIE 5")) || (strpos($user_agent, "MSIE 4")) || (strpos($user_agent, "MSIE 3"))) {
        echo "          <td class=\"text-center\"><a title=\"Add Time For: $h_empfullname\" href=\"timeadd.php?username=$username_qs\">Add</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Edit Time For: $h_empfullname\" href=\"timeedit.php?username=$username_qs\">Edit</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Time For: $h_empfullname\"
                    href=\"timedelete.php?username=$username_qs\">Delete</a></td></tr>\n";
    } else {
        echo "          <td class=\"text-center\"><a title=\"Add Time For: $h_empfullname\"
                    href=\"timeadd.php?username=$username_qs\">
                    <img border=0 src='../images/icons/clock_add.png'/></a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Edit Time For: $h_empfullname\"
                    href=\"timeedit.php?username=$username_qs\">
                    <img border=0 src='../images/icons/clock_edit.png'/></a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Time For: $h_empfullname\"
                    href=\"timedelete.php?username=$username_qs\">
                    <img border=0 src='../images/icons/clock_delete.png'/></a></td></tr>\n";
    }
}
echo "      </table>\n";
echo "      </div>\n";
echo "    </div>\n";
echo "  </div>\n";
echo "</div>\n";
include_once 'footer_bootstrap.php';
exit;
