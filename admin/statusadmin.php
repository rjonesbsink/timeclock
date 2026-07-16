<?php

require_once '../lib/session.php';
start_secure_session();

include '../config.inc.php';
include 'header_bootstrap.php';
include 'topmain_bootstrap.php';
echo "<title>$title - Status Summary</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

require_once '../lib/auth.php';
require_valid_user();

echo "<div class=\"container-fluid mt-3\">\n";
echo "  <div class=\"row\">\n";
$admin_leftnav_current = 'statusadmin.php';
include_once 'leftnav_bootstrap.php';
echo "    <div class=\"col-md-9\">\n";

echo "      <h5>Status Summary</h5>\n";
echo "      <div class=\"table-responsive\">\n";
echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
echo "        <tr>\n";
echo "          <th>&nbsp;</th>\n";
echo "          <th>Status Name</th>\n";
echo "          <th>On Punch</th>\n";
echo "          <th>Color</th>\n";
echo "          <th class=\"text-center\">In/Out</th>\n";
echo "          <th class=\"text-center\">Edit</th>\n";
echo "          <th class=\"text-center\">Delete</th>\n";
echo "        </tr>\n";

$row_count = 0;

$result = tc_select("*", "punchlist");

while ($row = mysqli_fetch_array($result)) {
    $punchitem = "" . $row['punchitems'] . "";
    $punchnext = "" . $row['punchnext'] . "";
    $color = "" . $row['color'] . "";
    $in_or_out = "" . $row['in_or_out'] . "";

    $row_count++;
    $row_color = ($row_count % 2) ? $color2 : $color1;

    if ($in_or_out == '1') {
        $in_or_out_tmp = 'In';
    } elseif ($in_or_out == '0') {
        $in_or_out_tmp = 'Out';
    }

    $h_punchitem = htmlentities($punchitem);
    $h_punchnext = htmlentities($punchnext);
    $h_color = htmlentities($color);
    $punchitem_qs = urlencode($punchitem);
    $punchnext_qs = urlencode($punchnext);

    echo "        <tr style=\"background-color:$row_color;\">\n";
    echo "          <td>$row_count</td>\n";
    echo "          <td><a title=\"Edit Status: $h_punchitem\" href=\"statusedit.php?statusname=$punchitem_qs\">$h_punchitem</a></td>\n";
    echo "          <td>" . ($punchnext ? "&rarr;&nbsp;" : "")
        . "<a title=\"Edit Status: $h_punchnext\" href=\"statusedit.php?statusname=$punchnext_qs\">$h_punchnext</a></td>\n";
    echo "          <td style=\"color:$h_color;\">$h_color</td>\n";
    echo "          <td class=\"text-center\">$in_or_out_tmp</td>\n";

    if ((strpos($user_agent, "MSIE 6")) || (strpos($user_agent, "MSIE 5")) || (strpos($user_agent, "MSIE 4")) || (strpos($user_agent, "MSIE 3"))) {
        echo "          <td class=\"text-center\"><a title=\"Edit Status: $h_punchitem\"
                    href=\"statusedit.php?statusname=$punchitem_qs\">Edit</a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Status: $h_punchitem\"
                    href=\"statusdelete.php?statusname=$punchitem_qs\">Delete</a></td></tr>\n";
    } else {
        echo "          <td class=\"text-center\"><a title=\"Edit Status: $h_punchitem\"
                    href=\"statusedit.php?statusname=$punchitem_qs\">
                    <img border=0 src='../images/icons/application_edit.png' /></a></td>\n";
        echo "          <td class=\"text-center\"><a title=\"Delete Status: $h_punchitem\"
                    href=\"statusdelete.php?statusname=$punchitem_qs\">
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
