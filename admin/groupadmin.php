<?php

require_once '../lib/session.php';
start_secure_session();

include '../config.inc.php';
include 'header_bootstrap.php';
include 'topmain_bootstrap.php';
echo "<title>$title - Group Summary</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

require_once '../lib/auth.php';
require_valid_user();

echo "<div class=\"container-fluid mt-3\">\n";
echo "  <div class=\"row\">\n";
$admin_leftnav_current = 'groupadmin.php';
include_once 'leftnav_bootstrap.php';
echo "    <div class=\"col-md-9\">\n";

echo "      <h5>Group Summary</h5>\n";
echo "      <div class=\"table-responsive\">\n";
echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
echo "        <tr>\n";
echo "          <th>&nbsp;</th>\n";
echo "          <th>Group Name</th>\n";
echo "          <th>Parent Office</th>\n";
echo "          <th class=\"text-center\">Users</th>\n";
echo "          <th class=\"text-center\">Edit</th>\n";
echo "          <th class=\"text-center\">Delete</th>\n";
echo "        </tr>\n";

$row_count = 0;

$result = tc_query(
    "select * from `" . $db_prefix . "groups`, " . $db_prefix . "offices where `" . $db_prefix . "groups`.officeid = " . $db_prefix . "offices.officeid
     order by " . $db_prefix . "offices.officename, `" . $db_prefix . "groups`.groupname"
);

while ($row = mysqli_fetch_array($result)) {
    $result2 = tc_select("`groups`", "employees", "`groups` = ? and office = ?", array($row['groupname'], $row['officename']));
    @$user_cnt = mysqli_num_rows($result2);

    $parent_office = $row['officename'];

    $row_count++;
    $row_color = ($row_count % 2) ? $color2 : $color1;

    $h_groupname = htmlentities($row['groupname']);
    $h_parent_office = htmlentities($parent_office);
    $groupname_qs = urlencode($row['groupname']);
    $officename_qs = urlencode($parent_office);

    echo "        <tr style=\"background-color:$row_color;\">\n";
    echo "          <td>$row_count</td>\n";
    echo "          <td><a title=\"Edit Group: $h_groupname\"
                    href=\"groupedit.php?groupname=$groupname_qs&officename=$officename_qs\">$h_groupname</a></td>\n";
    echo "          <td>$h_parent_office</td>\n";
    echo "          <td class=\"text-center\">$user_cnt</td>\n";

    if ((strpos($user_agent, "MSIE 6")) || (strpos($user_agent, "MSIE 5")) || (strpos($user_agent, "MSIE 4")) || (strpos($user_agent, "MSIE 3"))) {
        echo "          <td class=\"text-center\"><a title=\"Edit Group: $h_groupname\"
                    href=\"groupedit.php?groupname=$groupname_qs&officename=$officename_qs\">Edit</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Group: $h_groupname\"
                    href=\"groupdelete.php?groupname=$groupname_qs&officename=$officename_qs\">Delete</a></td></tr>\n";
    } else {
        echo "          <td class=\"text-center\"><a title=\"Edit Group: $h_groupname\"
                    href=\"groupedit.php?groupname=$groupname_qs&officename=$officename_qs\">
                    <img border=0 src='../images/icons/application_edit.png' /></a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Group: $h_groupname\"
                    href=\"groupdelete.php?groupname=$groupname_qs&officename=$officename_qs\">
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
