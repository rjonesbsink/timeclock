<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_colorpick_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Edit Status</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const WHERE_PUNCHITEMS = "punchitems = ?";
const FOOTER_PHP = 'footer_bootstrap.php';
const STATUSNAME_PATTERN = "^([[:alnum:]]| |-|_|.)+$";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    if (!isset($_GET['statusname'])) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"alert alert-danger\">\n";
        echo "    <h5>PHP Timeclock Error!</h5>\n";
        echo "    <p>How did you get here?</p>\n";
        echo "    <p>Go back to the <a href='statusadmin.php'>Status Summary</a> page to edit statuses.</p>\n";
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
        $punchnext = "" . $row['punchnext'] . "";
    }

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    // admin_leftnav_status_context needs raw (unescaped) values -- see the
    // doc-comment in leftnav_bootstrap.php.
    $admin_leftnav_status_context = array('statusname' => $get_status, 'current' => 'statusedit.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    echo "      <h5><img src='../images/icons/application_edit.png'> Edit Status</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_statusname'>New Status Name <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' id='post_statusname' class=\"form-control\" maxlength='50' name='post_statusname' value=\"" . htmlentities($punchitem) . "\">\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_color'>Color <span class=\"text-danger\">*</span></label>\n";
    echo "          <div class=\"input-group\" style=\"max-width:300px;\">\n";
    echo "            <input type='text' id='post_color' class=\"form-control\" maxlength='7' name='post_color' value=\"" . htmlentities($color) . "\">\n";
    echo "            <a href=\"#\" class=\"btn btn-outline-secondary\"
                onclick=\"cp.select(document.forms['form'].post_color,'pick');return false;\" name=\"pick\" id=\"pick\">Pick Color</a>\n";
    echo "          </div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Is Status considered <b>In</b> or <b>Out</b>?</label>\n";

    if ($in_or_out == '1') {
        echo "          <div class=\"form-check form-check-inline\"><input checked type='radio' class=\"form-check-input\" name='create_status' value='1'
                id='create_status_y'><label class=\"form-check-label\" for='create_status_y'>In</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_status' value='0'
                id='create_status_n'><label class=\"form-check-label\" for='create_status_n'>Out</label></div>\n";
    } elseif ($in_or_out == '0') {
        echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_status' value='1'
                id='create_status_y'><label class=\"form-check-label\" for='create_status_y'>In</label></div>\n";
        echo "          <div class=\"form-check form-check-inline\"><input checked type='radio' class=\"form-check-input\" name='create_status' value='0'
                id='create_status_n'><label class=\"form-check-label\" for='create_status_n'>Out</label></div>\n";
    } else {
        echo "Status is not defined.\n";
        exit;
    }

    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='punchnext'>On Punch Become</label>\n";
    echo "          <select id='punchnext' class=\"form-select\" name='punchnext'>\n";
    echo "            <option value=''>...</option>\n";
    echo html_options(tc_select("punchitems", "punchlist"), $punchnext);
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <script language=\"javascript\">cp.writeDiv()</script>\n";
    echo "        <input type='hidden' name='get_status' value=\"$h_get_status\">\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit Status'>Edit Status</button>\n";
    echo "        <a href='statusadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $get_status = post_string('get_status');
    $h_get_status = htmlentities($get_status);
    $post_statusname = post_string('post_statusname');
    $post_color = post_string('post_color');
    $create_status = $_POST['create_status'];
    $punchnext = post_string('punchnext');

    // begin post validation //

    if (!empty($get_status)) {
        $getstatus = tc_select_value("punchitems", "punchlist", WHERE_PUNCHITEMS, $get_status);
        if (!isset($getstatus)) {
            echo "Status is not defined.\n";
            exit;
        }
    }

    $punchnext_ok = true;
    if (has_value($punchnext)) {
        $punchnext_ok = ($punchnext == tc_select_value("punchitems", "punchlist", WHERE_PUNCHITEMS, $punchnext));
    }

    if (($create_status !== '0') && ($create_status !== '1')) {
        exit;
    }

    $string  = strstr($post_statusname, "'");
    $string2 = strstr($post_statusname, "\"");
    $status_name_exists = ($post_statusname !== $get_status) && entity_name_exists("punchlist", "punchitems", $post_statusname);

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    // admin_leftnav_status_context needs raw (unescaped) values -- see the
    // doc-comment in leftnav_bootstrap.php. Points at the not-yet-renamed
    // status while redisplaying validation errors, or the newly-renamed
    // status once the update below has actually happened.
    $evil_status = (
        (empty($post_statusname)) ||
        (empty($post_color)) ||
        (!preg_match('/' . STATUSNAME_PATTERN . '/i', $post_statusname)) ||
        ((!preg_match('/' . "^(#[a-fA-F0-9]{6})+$" . '/i', $post_color)) &&
        (!preg_match('/' . "^([a-fA-F0-9]{6})+$" . '/i', $post_color))) ||
        (!empty($string)) ||
        (!empty($string2)) ||
        ($status_name_exists) ||
        !$punchnext_ok
    );

    if ($evil_status) {
        $admin_leftnav_status_context = array('statusname' => $get_status, 'current' => 'statusedit.php');
    } else {
        $admin_leftnav_status_context = array('statusname' => $post_statusname, 'current' => 'statusedit.php');
    }
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    echo "      <h5><img src='../images/icons/application_edit.png'> Edit Status</h5>\n";

    if ($evil_status) {
        if (empty($post_statusname)) {
            echo "      <div class=\"alert alert-danger\">A Status Name is required.</div>\n";
        } elseif (empty($post_color)) {
            echo "      <div class=\"alert alert-danger\">A Color is required.</div>\n";
        } elseif (!empty($string)) {
            echo "      <div class=\"alert alert-danger\">Apostrophes are not allowed.</div>\n";
        } elseif (!empty($string2)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed.</div>\n";
        } elseif ($status_name_exists) {
            echo "      <div class=\"alert alert-danger\">Status already exists. Create another status.</div>\n";
        } elseif (!preg_match('/' . STATUSNAME_PATTERN . '/i', $post_statusname)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, underscores, spaces, and periods are allowed
                    when editing a Status Name.</div>\n";
        } elseif ((!preg_match('/' . "^(#[a-fA-F0-9]{6})+$" . '/i', $post_color)) && (!preg_match('/' . "^([a-fA-F0-9]{6})+$" . '/i', $post_color))) {
            echo "      <div class=\"alert alert-danger\">The '#' symbol followed by letters A-F, or numbers 0-9 are allowed when editing
                    a Color.</div>\n";
        } elseif (!$punchnext_ok) {
            echo "      <div class=\"alert alert-danger\">\"On Punch\" target is invalid!</div>\n";
        }

        $h_post_statusname = htmlentities($post_statusname);
        $h_post_color = htmlentities($post_color);

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='post_statusname'>New Status Name <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' id='post_statusname' class=\"form-control\" maxlength='50' name='post_statusname' value=\"$h_post_statusname\">\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='post_color'>Color <span class=\"text-danger\">*</span></label>\n";
        echo "          <div class=\"input-group\" style=\"max-width:300px;\">\n";
        echo "            <input type='text' id='post_color' class=\"form-control\" maxlength='7' name='post_color' value=\"$h_post_color\">\n";
        echo "            <a href=\"#\" class=\"btn btn-outline-secondary\"
                    onclick=\"cp.select(document.forms['form'].post_color,'pick');return false;\" name=\"pick\" id=\"pick\">Pick Color</a>\n";
        echo "          </div>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label d-block\">Is Status considered <b>In</b> or <b>Out</b>?</label>\n";

        if ($create_status == '1') {
            echo "          <div class=\"form-check form-check-inline\"><input checked type='radio' class=\"form-check-input\" name='create_status' value='1'
                    id='create_status_y'><label class=\"form-check-label\" for='create_status_y'>In</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_status' value='0'
                    id='create_status_n'><label class=\"form-check-label\" for='create_status_n'>Out</label></div>\n";
        } elseif ($create_status == '0') {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_status' value='1'
                    id='create_status_y'><label class=\"form-check-label\" for='create_status_y'>In</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input checked type='radio' class=\"form-check-input\" name='create_status' value='0'
                    id='create_status_n'><label class=\"form-check-label\" for='create_status_n'>Out</label></div>\n";
        }

        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='punchnext'>On Punch Become</label>\n";
        echo "          <select id='punchnext' class=\"form-select\" name='punchnext'>\n";
        echo "            <option value=''>...</option>\n";
        echo html_options(tc_select("punchitems", "punchlist"), $punchnext);
        echo "          </select>\n";
        echo "        </div>\n";
        echo "        <p class=\"small text-muted\">* required</p>\n";
        echo "        <script language=\"javascript\">cp.writeDiv()</script>\n";
        echo "        <input type='hidden' name='get_status' value=\"$h_get_status\">\n";
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit Status'>Edit Status</button>\n";
        echo "        <a href='statusadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    } else {
        tc_update_strings(
            "punchlist",
            array(
                "punchitems" => $post_statusname,
                "color"      => $post_color,
                "in_or_out"  => $create_status,
                "punchnext"  => $punchnext
            ),
            WHERE_PUNCHITEMS,
            $get_status
        );

        if ($post_statusname != $get_status) {
            tc_update_strings(
                "info",
                array("inout" => $post_statusname),
                "`inout` = ?",
                $get_status
            );
        }

        $h_post_statusname = htmlentities($post_statusname);
        $h_post_color = htmlentities($post_color);
        $h_punchnext = htmlentities($punchnext);

        echo "      <div class=\"alert alert-success\">Status properties updated successfully.</div>\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>New Status Name:</th><td>$h_post_statusname</td></tr>\n";
        echo "        <tr><th>Color:</th><td>$h_post_color</td></tr>\n";

        $create_status_tmp = ($create_status == '1') ? 'In' : 'Out';

        echo "        <tr><th>Is Status considered <b>In</b> or <b>Out</b>?</th><td>$create_status_tmp</td></tr>\n";
        echo "        <tr><th>On Punch:</th><td>$h_punchnext</td></tr>\n";
        echo "      </table>\n";
        echo "      <a href='statusadmin.php' class=\"btn btn-primary\">Done</a>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
    }
    include_once FOOTER_PHP;
    exit;
}
