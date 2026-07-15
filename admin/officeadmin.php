<?php

require_once '../lib/session.php';
start_secure_session();

include '../config.inc.php';
include 'header_bootstrap.php';
include 'topmain_bootstrap.php';
echo "<title>$title - Office Summary</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

require_once '../lib/auth.php';
require_valid_user();

echo "<div class=\"container-fluid mt-3\">\n";
echo "  <div class=\"row\">\n";
$admin_leftnav_current = 'officeadmin.php';
include_once 'leftnav_bootstrap.php';
echo "    <div class=\"col-md-9\">\n";

echo "      <h5>Office Summary</h5>\n";
echo "      <div class=\"table-responsive\">\n";
echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
echo "        <tr>\n";
echo "          <th>&nbsp;</th>\n";
echo "          <th>Office Name</th>\n";
echo "          <th class=\"text-center\">Groups</th>\n";
echo "          <th class=\"text-center\">Users</th>\n";
echo "          <th class=\"text-center\">Edit</th>\n";
echo "          <th class=\"text-center\">Delete</th>\n";
echo "        </tr>\n";

$row_count = 0;

$result = tc_query("select * from " . $db_prefix . "offices order by officename");

while ($row = mysqli_fetch_array($result)) {
    $result2 = tc_select("office", "employees", "office = ?", $row['officename']);
    @$user_cnt = mysqli_num_rows($result2);

    $result3 = tc_select("*", "groups", "officeid = ?", $row['officeid']);
    @$group_cnt = mysqli_num_rows($result3);

    $row_count++;
    $row_color = ($row_count % 2) ? $color2 : $color1;

    $h_officename = htmlentities($row['officename']);
    $officename_qs = urlencode($row['officename']);

    echo "        <tr style=\"background-color:$row_color;\">\n";
    echo "          <td>$row_count</td>\n";
    echo "          <td><a title=\"Edit Office: $h_officename\"
                    href=\"officeedit.php?officename=$officename_qs\">$h_officename</a></td>\n";
    echo "          <td class=\"text-center\">$group_cnt</td>\n";
    echo "          <td class=\"text-center\">$user_cnt</td>\n";

    if ((strpos($user_agent, "MSIE 6")) || (strpos($user_agent, "MSIE 5")) || (strpos($user_agent, "MSIE 4")) || (strpos($user_agent, "MSIE 3"))) {
        echo "          <td class=\"text-center\"><a title=\"Edit Office: $h_officename\"
                    href=\"officeedit.php?officename=$officename_qs\">Edit</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Office: $h_officename\"
                    href=\"officedelete.php?officename=$officename_qs\">Delete</a></td></tr>\n";
    } else {
        echo "          <td class=\"text-center\"><a title=\"Edit Office: $h_officename\"
                    href=\"officeedit.php?officename=$officename_qs\">
                    <img border=0 src='../images/icons/application_edit.png' /></a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Office: $h_officename\"
                    href=\"officedelete.php?officename=$officename_qs\">
                    <img border=0 src='../images/icons/delete.png' /></a></td></tr>\n";
    }
}
echo "      </table>\n";
echo "      </div>\n";
echo "    </div>\n";
echo "  </div>\n";
echo "</div>\n";
include_once 'footer_bootstrap.php';
exit;
