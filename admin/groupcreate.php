<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Create Group</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
const FOOTER_PHP = 'footer_bootstrap.php';
const GROUPNAME_PATTERN = "^([[:alnum:]]| |-|_|\.)+$";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'groupcreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <h5><img src='../images/icons/group_add.png'> Create Group</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_groupname'>Group Name <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' id='post_groupname' class=\"form-control\" maxlength='50' name='post_groupname'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='select_office_name'>Parent Office <span class=\"text-danger\">*</span></label>\n";
    echo "          <select id='select_office_name' class=\"form-select\" name='select_office_name'>\n";
    echo "            <option value='1'>Choose One</option>\n";
    echo html_options(tc_select("officename", "offices", "1=1 order by officename asc"));
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Group'>Create Group</button>\n";
    echo "        <a href='groupadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $select_office_name = post_string('select_office_name');
    $post_groupname = post_string('post_groupname');

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'groupcreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $post_groupname = stripslashes($post_groupname);
    $select_office_name = stripslashes($select_office_name);
    $post_groupname = addslashes($post_groupname);
    $select_office_name = addslashes($select_office_name);

    // begin post validation //

    if (!empty($select_office_name)) {
        $result = tc_select("*", "offices", "officename = ?", $select_office_name);
        while ($row = mysqli_fetch_array($result)) {
            $getoffice = "" . $row['officename'] . "";
            $officeid = "" . $row['officeid'] . "";
        }
    }
    if ((!isset($getoffice)) && ($select_office_name != '1')) {
        echo "Office is not defined for this user. Go back and associate this user with an office.\n";
        exit;
    }

    // check for duplicate groupnames with matching officeids //

    $group_name_exists = entity_name_exists("groups", "groupname", $post_groupname, "officeid = ?", array(@$officeid));

    $string = strstr($post_groupname, "\'");
    $string2 = strstr($post_groupname, "\"");

    if (
        (!empty($string)) || (empty($post_groupname)) || (!preg_match('/' . GROUPNAME_PATTERN . '/i', $post_groupname)) ||
        ($select_office_name == '1') || ($group_name_exists) || (!empty($string2))
    ) {
        if (!empty($string)) {
            echo "      <div class=\"alert alert-danger\">Apostrophes are not allowed when creating a Group Name.</div>\n";
        } elseif (!empty($string2)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed when creating a Group Name.</div>\n";
        } elseif (empty($post_groupname)) {
            echo "      <div class=\"alert alert-danger\">A Group Name is required.</div>\n";
        } elseif (!preg_match('/' . GROUPNAME_PATTERN . '/i', $post_groupname)) {
            echo "      <div class=\"alert alert-danger\">Only alphanumeric characters, hyphens, underscores, spaces, and periods are
                    allowed when creating a Group Name.</div>\n";
        } elseif ($select_office_name == '1') {
            echo "      <div class=\"alert alert-danger\">A Parent Office must be chosen.</div>\n";
        } elseif ($group_name_exists) {
            echo "      <div class=\"alert alert-danger\">Group already exists. Create another group.</div>\n";
        }

        // end post validation //

        if (!empty($string)) {
            $post_groupname = stripslashes($post_groupname);
        }
        if (!empty($string2)) {
            $post_groupname = stripslashes($post_groupname);
        }

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='post_groupname'>Group Name <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' id='post_groupname' class=\"form-control\" maxlength='50' name='post_groupname' value=\""
            . htmlentities($post_groupname) . "\">\n";
        echo "        </div>\n";

        if (!empty($string)) {
            $post_groupname = addslashes($post_groupname);
        }
        if (!empty($string2)) {
            $post_groupname = addslashes($post_groupname);
        }

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='select_office_name'>Parent Office <span class=\"text-danger\">*</span></label>\n";
        echo "          <select id='select_office_name' class=\"form-select\" name='select_office_name'>\n";
        echo "            <option value='1'>Choose One</option>\n";
        echo html_options(tc_select("officename", "offices", "1=1 order by officename asc"), $select_office_name);
        echo "          </select>\n";
        echo "        </div>\n";

        echo "        <p class=\"small text-muted\">* required</p>\n";
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Group'>Create Group</button>\n";
        echo "        <a href='groupadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    } else {
        tc_insert_strings("groups", array("groupname" => $post_groupname, "officeid" => $officeid));

        echo "      <div class=\"alert alert-success\">Group created successfully.</div>\n";
        echo "      <h5><img src='../images/icons/group_add.png'> Create Group</h5>\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Group Name:</th><td>" . htmlentities($post_groupname) . "</td></tr>\n";
        echo "        <tr><th>Parent Office:</th><td>" . htmlentities($select_office_name) . "</td></tr>\n";
        echo "      </table>\n";
        echo "      <a href='groupcreate.php' class=\"btn btn-primary\">Done</a>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }
}
