<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Delete Status</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const WHERE_PUNCHITEMS = "punchitems = ?";
const FOOTER_PHP = 'footer_bootstrap.php';

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    if (!isset($_GET['statusname'])) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"alert alert-danger\">\n";
        echo "    <h5>PHP Timeclock Error!</h5>\n";
        echo "    <p>How did you get here?</p>\n";
        echo "    <p>Go back to the <a href='statusadmin.php'>Status Summary</a> page to delete statuses.</p>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    $get_status = get_string('statusname');
    $h_get_status = htmlentities($get_status);

    $result = tc_select("*", "punchlist", WHERE_PUNCHITEMS, $get_status);

    while ($row = mysqli_fetch_array($result)) {
        $punchitem = "" . $row['punchitems'] . "";
        $color = "" . $row['color'] . "";
        $in_or_out = "" . $row['in_or_out'] . "";
    }

    if ($in_or_out == '1') {
        $in_or_out_tmp = 'In';
    } elseif ($in_or_out == '0') {
        $in_or_out_tmp = 'Out';
    } else {
        echo "Status is not defined.\n";
        exit;
    }

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    // admin_leftnav_status_context needs raw (unescaped) values -- see the
    // doc-comment in leftnav_bootstrap.php.
    $admin_leftnav_status_context = array('statusname' => $get_status, 'current' => 'statusdelete.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    echo "      <h5><img src='../images/icons/application_delete.png'> Delete Status</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
    echo "        <tr><th>Status Name:</th><td><input type='hidden' name='post_statusname' value=\"" . htmlentities($punchitem) . "\">"
        . htmlentities($punchitem) . "</td></tr>\n";
    echo "        <tr><th>Color:</th><td><input type='hidden' name='post_color' value=\"" . htmlentities($color) . "\">"
        . htmlentities($color) . "</td></tr>\n";
    echo "        <tr><th>Is Status considered <b>In</b> or <b>Out</b>?</th><td><input type='hidden' name='post_in_out' value=\"$in_or_out_tmp\">"
        . "$in_or_out_tmp</td></tr>\n";
    echo "      </table>\n";
    echo "      <div class=\"alert alert-warning\">Deleting this status does NOT delete it from the database history. It merely removes
                it from the list of status choices.</div>\n";
    echo "      <button type='submit' class=\"btn btn-danger\" name='submit' value='Delete Status'>Delete Status</button>\n";
    echo "      <a href='statusadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $post_statusname = post_string('post_statusname');
    $post_color = post_string('post_color');
    $post_in_out = post_string('post_in_out');

    if ($post_in_out == 'In') {
        $post_in_out = '1';
    } elseif ($post_in_out == 'Out') {
        $post_in_out = '0';
    } else {
        exit;
    }

    $result = tc_select("*", "punchlist", WHERE_PUNCHITEMS, $post_statusname);

    while ($row = mysqli_fetch_array($result)) {
        $punchitem = "" . $row['punchitems'] . "";
        $color = "" . $row['color'] . "";
        $in_or_out = "" . $row['in_or_out'] . "";
    }

    if (($post_statusname != $punchitem) || ($post_color != $color) || ($post_in_out != $in_or_out)) {
        exit;
    }

    tc_delete("punchlist", WHERE_PUNCHITEMS, $post_statusname);

    $h_post_statusname = htmlentities($post_statusname);
    $h_post_color = htmlentities($post_color);

    if ($post_in_out == '1') {
        $confirm_in_out = 'In';
    } elseif ($post_in_out == '0') {
        $confirm_in_out = 'Out';
    } else {
        exit;
    }

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'statusadmin.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <div class=\"alert alert-success\">Status deleted successfully.</div>\n";
    echo "      <h5><img src='../images/icons/application_delete.png'> Delete Status</h5>\n";
    echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
    echo "        <tr><th>Status Name:</th><td>$h_post_statusname</td></tr>\n";
    echo "        <tr><th>Color:</th><td>$h_post_color</td></tr>\n";
    echo "        <tr><th>Is Status considered <b>In</b> or <b>Out</b>?</th><td>$confirm_in_out</td></tr>\n";
    echo "      </table>\n";
    echo "      <a href='statusadmin.php' class=\"btn btn-primary\">Done</a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
}
