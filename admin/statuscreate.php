<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_colorpick_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Create Status</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
const FOOTER_PHP = 'footer_bootstrap.php';
const STATUSNAME_PATTERN = "^([[:alnum:]]| |-|_|\.)+$";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'statuscreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <h5><img src='../images/icons/application_add.png'> Create Status</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_statusname'>Status Name <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' id='post_statusname' class=\"form-control\" maxlength='50' name='post_statusname'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_color'>Color <span class=\"text-danger\">*</span></label>\n";
    echo "          <div class=\"input-group\" style=\"max-width:300px;\">\n";
    echo "            <input type='text' id='post_color' class=\"form-control\" maxlength='7' name='post_color'>\n";
    echo "            <a href=\"#\" class=\"btn btn-outline-secondary\"
                    onclick=\"cp.select(document.forms['form'].post_color,'pick');return false;\" name=\"pick\" id=\"pick\">Pick Color</a>\n";
    echo "          </div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Is Status considered <b>In</b> or <b>Out</b>?</label>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_status' value='1' id='create_status_y'>
                <label class=\"form-check-label\" for='create_status_y'>In</label></div>\n";
    echo "          <div class=\"form-check form-check-inline\"><input checked type='radio' class=\"form-check-input\" name='create_status' value='0' id='create_status_n'>
                <label class=\"form-check-label\" for='create_status_n'>Out</label></div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='punchnext'>On Punch Become</label>\n";
    echo "          <select id='punchnext' class=\"form-select\" name='punchnext'>\n";
    echo "            <option value=''>...</option>\n";
    echo html_options(tc_select("punchitems", "punchlist"));
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <script language=\"javascript\">cp.writeDiv()</script>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Status'>Create Status</button>\n";
    echo "        <a href='statusadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
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
    $create_status = $_POST['create_status'];
    $punchnext = post_string('punchnext');

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'statuscreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    // begin post validation //

    if (($create_status !== '0') && ($create_status !== '1')) {
        exit;
    }

    $string  = strstr($post_statusname, "'");
    $string2 = strstr($post_statusname, "\"");

    $dupe = empty($string) && entity_name_exists("punchlist", "punchitems", $post_statusname);

    $punchnext_ok = true;
    if (has_value($punchnext)) {
        $punchnext_ok = ($punchnext == tc_select_value("punchitems", "punchlist", "punchitems = ?", $punchnext));
    }

    if (
        (empty($post_statusname)) ||
        (empty($post_color)) ||
        (!preg_match('/' . STATUSNAME_PATTERN . '/i', $post_statusname)) ||
        ($dupe) ||
        ((!preg_match('/' . "^(#[a-fA-F0-9]{6})+$" . '/i', $post_color)) &&
        (!preg_match('/' . "^([a-fA-F0-9]{6})+$" . '/i', $post_color))) ||
        (!empty($string)) ||
        (!empty($string2)) ||
        !$punchnext_ok
    ) {
        if (empty($post_statusname)) {
            echo "      <div class=\"alert alert-danger\">A Status Name is required.</div>\n";
        } elseif (empty($post_color)) {
            echo "      <div class=\"alert alert-danger\">A Color is required.</div>\n";
        } elseif (!empty($string)) {
            echo "      <div class=\"alert alert-danger\">Apostrophes are not allowed.</div>\n";
        } elseif (!empty($string2)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed.</div>\n";
        } elseif (!preg_match('/' . STATUSNAME_PATTERN . '/i', $post_statusname)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, underscores, spaces, and periods are allowed
                    when editing a Status Name.</div>\n";
        } elseif ((!preg_match('/' . "^(#[a-fA-F0-9]{6})+$" . '/i', $post_color)) && (!preg_match('/' . "^([a-fA-F0-9]{6})+$" . '/i', $post_color))) {
            echo "      <div class=\"alert alert-danger\">The '#' symbol followed by letters A-F, or numbers 0-9 are allowed when editing
                    a Color.</div>\n";
        } elseif ($dupe) {
            echo "      <div class=\"alert alert-danger\">Status already exists. Create another status.</div>\n";
        } elseif (!$punchnext_ok) {
            echo "      <div class=\"alert alert-danger\">\"On Punch\" target is invalid!</div>\n";
        }

        $h_post_statusname = htmlentities($post_statusname);
        $h_post_color = htmlentities($post_color);

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='post_statusname'>Status Name <span class=\"text-danger\">*</span></label>\n";
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
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Status'>Create Status</button>\n";
        echo "        <a href='statusadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    } else {
        tc_insert_strings(
            "punchlist",
            array(
                "punchitems" => $post_statusname,
                "color"      => $post_color,
                "in_or_out"  => $create_status,
                "punchnext"  => $punchnext
            )
        );

        $h_post_statusname = htmlentities($post_statusname);
        $h_post_color = htmlentities($post_color);
        $h_punchnext = htmlentities($punchnext);

        echo "      <div class=\"alert alert-success\">Status created successfully.</div>\n";
        echo "      <h5><img src='../images/icons/application_add.png'> Create Status</h5>\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Status Name:</th><td>$h_post_statusname</td></tr>\n";
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
