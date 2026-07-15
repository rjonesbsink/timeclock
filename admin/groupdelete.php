<?php

require_once '../lib/session.php';
start_secure_session();

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];

const WHERE_OFFICE_AND_GROUPS = "office = ? and `groups` = ?";
const FOOTER_PHP = 'footer_bootstrap.php';
const MSG_OFFICE_NOT_DEFINED = "Office name is not defined for this group.\n";
const MSG_GROUP_NOT_DEFINED = "Group name is not defined for this group.\n";

include_once '../config.inc.php';
if ($request !== 'POST') {
    include_once 'header_get_bootstrap.php';
    include_once 'topmain_bootstrap.php';
}
echo "<title>$title - Delete Group</title>\n";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    if ((!isset($_GET['groupname'])) || (!isset($_GET['officename']))) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"alert alert-danger\">\n";
        echo "    <h5>PHP Timeclock Error!</h5>\n";
        echo "    <p>How did you get here?</p>\n";
        echo "    <p>Go back to the <a href='groupadmin.php'>Group Summary</a> page to delete groups.</p>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    $get_group = get_string('groupname');
    $get_office = get_string('officename');
    $h_get_group = htmlentities($get_group);
    $h_get_office = htmlentities($get_office);

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    // admin_leftnav_group_context needs raw (unescaped) values -- see the
    // doc-comment in leftnav_bootstrap.php.
    $admin_leftnav_group_context = array('groupname' => $get_group, 'officename' => $get_office, 'current' => 'groupdelete.php');
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $result = tc_select("*", "`groups`, " . $db_prefix . "offices", "officename = ? and groupname = ?", array($get_office, $get_group));

    while ($row = mysqli_fetch_array($result)) {
        $officename = "" . $row['officename'] . "";
        $officeid = "" . $row['officeid'] . "";
        $groupname = "" . $row['groupname'] . "";
        $groupid = "" . $row['groupid'] . "";
    }

    if (!isset($officename)) {
        echo MSG_OFFICE_NOT_DEFINED;
        exit;
    }
    if (!isset($groupname)) {
        echo MSG_GROUP_NOT_DEFINED;
        exit;
    }

    $result2 = tc_select("*", "employees", WHERE_OFFICE_AND_GROUPS, array($get_office, $get_group));
    @$user_cnt = mysqli_num_rows($result2);

    echo "      <h5><img src='../images/icons/group_delete.png'> Delete Group</h5>\n";

    if ($user_cnt > 0) {
        if ($user_cnt == 1) {
            echo "      <div class=\"alert alert-danger\">This group contains $user_cnt user. This user must be moved to another group
                    before it can be deleted.</div>\n";
        } else {
            echo "      <div class=\"alert alert-danger\">This group contains $user_cnt users. These users must be moved to another
                    group before it can be deleted.</div>\n";
        }
    }

    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
    echo "        <tr><th>Group Name:</th><td><input type='hidden' name='post_groupname' value=\"" . htmlentities($groupname) . "\">$h_get_group</td></tr>\n";
    echo "        <tr><th>Parent Office:</th><td><input type='hidden' name='post_officename' value=\"" . htmlentities($officename) . "\">$h_get_office</td></tr>\n";
    echo "        <tr><th>User Count:</th><td><input type='hidden' name='user_cnt' value=\"" . htmlentities($user_cnt) . "\">"
        . htmlentities($user_cnt) . "</td></tr>\n";
    echo "      </table>\n";

    if ($user_cnt == 0) {
        echo "      <input type='hidden' name='group_name_no_users'>\n";
        echo "      <input type='hidden' name='office_name_no_users'>\n";
    } else {
        $move_msg = ($user_cnt == 1) ? "Move this user to which office?" : "Move these users to which office?";
        echo "      <div class=\"mb-3\">\n";
        echo "        <label class=\"form-label\">$move_msg</label>\n";
        echo "        <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
        echo "        </select>\n";
        echo "      </div>\n";
        echo "      <div class=\"mb-3\">\n";
        echo "        <label class=\"form-label\">Which Group?</label>\n";
        echo "        <select class=\"form-select\" name='group_name' onfocus='group_names();'>\n";
        echo "          <option selected></option>\n";
        echo "        </select>\n";
        echo "      </div>\n";
    }

    echo "      <input type='hidden' name='post_officeid' value=\"$officeid\">\n";
    echo "      <input type='hidden' name='post_groupid' value=\"$groupid\">\n";
    echo "      <button type='submit' class=\"btn btn-danger\" name='submit' value='Delete Group'>Delete Group</button>\n";
    echo "      <a href='groupadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    include_once 'header_post_bootstrap.php';
    include_once 'topmain_bootstrap.php';

    $post_officename = post_string('post_officename');
    $post_officeid = post_string('post_officeid');
    // office_name/group_name are only present in the real form when the
    // group has users to move; downstream isset() checks distinguish "not
    // submitted" (0-user delete) from "submitted but empty" (user hasn't
    // picked a destination yet), so default to null rather than '' to keep
    // that distinction intact while still rejecting array-type-confusion.
    $group_name = post_string('group_name', null);
    $office_name = post_string('office_name', null);
    @$group_name_no_users = $_POST['group_name_no_users'];
    @$office_name_no_users = $_POST['office_name_no_users'];
    $post_groupname = post_string('post_groupname');
    $post_groupid = post_string('post_groupid');
    $user_cnt = $_POST['user_cnt'];

    // begin post validation //

    if ((!empty($post_officename)) || (!empty($post_officeid)) || ($office_name != 'no_office_users')) {
        $result = tc_select("*", "offices", "officename = ? and officeid = ?", array($post_officename, $post_officeid));
        while ($row = mysqli_fetch_array($result)) {
            $officename = "" . $row['officename'] . "";
            $officeid = "" . $row['officeid'] . "";
        }
    }
    if ((!isset($officename)) || (!isset($officeid))) {
        echo MSG_OFFICE_NOT_DEFINED;
        exit;
    }

    if ((!empty($post_groupname)) || (!empty($post_groupid)) || ($group_name != 'no_group_users')) {
        $result = tc_select("*", "groups", "groupname = ? and groupid = ?", array($post_groupname, $post_groupid));
        while ($row = mysqli_fetch_array($result)) {
            $groupname = "" . $row['groupname'] . "";
            $groupid = "" . $row['groupid'] . "";
        }
    }
    if ((!isset($groupname)) || (!isset($groupid))) {
        echo MSG_GROUP_NOT_DEFINED;
        exit;
    }

    if (!empty($office_name)) {
        $result = tc_select("*", "offices", "officename = ?", $office_name);
        while ($row = mysqli_fetch_array($result)) {
            $tmp_officename = "" . $row['officename'] . "";
            $tmp_officeid = "" . $row['officeid'] . "";
        }
        if ((!isset($tmp_officename)) || (!isset($tmp_officeid))) {
            echo MSG_OFFICE_NOT_DEFINED;
            exit;
        }
    }

    if (!empty($group_name)) {
        $result = tc_select("*", "groups", "groupname = ?", $group_name);
        while ($row = mysqli_fetch_array($result)) {
            $tmp_groupname = "" . $row['groupname'] . "";
            $tmp_groupid = "" . $row['groupid'] . "";
        }
        if ((!isset($tmp_groupname)) || (!isset($tmp_groupid))) {
            echo MSG_GROUP_NOT_DEFINED;
            exit;
        }
    }

    if (isset($office_name_no_users)) {
        if (!empty($office_name_no_users)) {
            echo "Something is fishy here.\n";
            exit;
        }
    }
    if (isset($group_name_no_users)) {
        if (!empty($group_name_no_users)) {
            echo "Something is fishy here.\n";
            exit;
        }
    }

    $result = tc_select("*", "employees", WHERE_OFFICE_AND_GROUPS, array($post_officename, $post_groupname));
    @$tmp_user_cnt = mysqli_num_rows($result);

    if ($user_cnt != $tmp_user_cnt) {
        echo "Posted user count does not equal actual user count for this group.\n";
        exit;
    }

    // end post validation //

    $h_post_officename = htmlentities($post_officename);
    $h_post_groupname = htmlentities($post_groupname);

    $evil_delete = ((isset($office_name)) && (empty($office_name))) || ((isset($group_name)) && (empty($group_name))) ||
        (($group_name == $post_groupname) && ($office_name == $post_officename));

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    if ($evil_delete) {
        // admin_leftnav_group_context needs raw (unescaped) values -- see the
        // doc-comment in leftnav_bootstrap.php. The group still exists (the
        // delete was rejected), so its Edit/Delete sub-links stay live.
        $admin_leftnav_group_context = array('groupname' => $post_groupname, 'officename' => $post_officename, 'current' => 'groupdelete.php');
        include_once 'leftnav_bootstrap.php';
    } else {
        // The group no longer exists after a successful delete -- fall back
        // to the plain sidebar instead of linking to a now-dead group.
        $admin_leftnav_current = 'groupadmin.php';
        include_once 'leftnav_bootstrap.php';
    }
    echo "    <div class=\"col-md-9\">\n";

    echo "      <h5><img src='../images/icons/group_delete.png'> Delete Group</h5>\n";

    if ($evil_delete) {
        if (((isset($office_name)) && (empty($office_name))) || ((isset($group_name)) && (empty($group_name)))) {
            echo "      <div class=\"alert alert-danger\">To delete this group, you must choose to move its' current users to
                    another office <b>AND/OR</b> group.</div>\n";
        } elseif (($group_name == $post_groupname) && ($office_name == $post_officename)) {
            echo "      <div class=\"alert alert-danger\">To delete this group, you must choose to move its' current users to
                    <b>ANOTHER</b> group.</div>\n";
        }

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Group Name:</th><td><input type='hidden' name='post_groupname' value=\"$h_post_groupname\">$h_post_groupname</td></tr>\n";
        echo "        <tr><th>Parent Office:</th><td><input type='hidden' name='post_officename' value=\"$h_post_officename\">$h_post_officename</td></tr>\n";
        echo "        <tr><th>User Count:</th><td><input type='hidden' name='user_cnt' value=\"" . htmlentities($user_cnt) . "\">"
            . htmlentities($user_cnt) . "</td></tr>\n";
        echo "      </table>\n";

        if ($user_cnt > 0) {
            $move_msg = ($user_cnt == 1) ? "Move this user to which office?" : "Move these users to which office?";
            echo "      <div class=\"mb-3\">\n";
            echo "        <label class=\"form-label\">$move_msg</label>\n";
            echo "        <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
            echo "        </select>\n";
            echo "      </div>\n";
            echo "      <div class=\"mb-3\">\n";
            echo "        <label class=\"form-label\">Which Group?</label>\n";
            echo "        <select class=\"form-select\" name='group_name' onfocus='group_names();'>\n";
            echo "          <option selected></option>\n";
            echo "        </select>\n";
            echo "      </div>\n";
        }

        echo "      <input type='hidden' name='post_officeid' value=\"$post_officeid\">\n";
        echo "      <input type='hidden' name='post_groupid' value=\"$post_groupid\">\n";
        echo "      <button type='submit' class=\"btn btn-danger\" name='submit' value='Delete Group'>Delete Group</button>\n";
        echo "      <a href='groupadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    } else {
        if ($user_cnt > 0) {
            tc_update_strings(
                "employees",
                array("office" => $office_name, "groups" => $group_name),
                WHERE_OFFICE_AND_GROUPS,
                array($post_officename, $post_groupname)
            );
        }

        tc_delete("groups", "groupid = ?", $post_groupid);

        echo "      <div class=\"alert alert-success\">Group deleted successfully.</div>\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Group Name:</th><td>$h_post_groupname</td></tr>\n";
        echo "        <tr><th>Parent Office:</th><td>$h_post_officename</td></tr>\n";
        echo "        <tr><th>User Count:</th><td>" . htmlentities($user_cnt) . "</td></tr>\n";
        echo "      </table>\n";
        echo "      <a href='groupadmin.php' class=\"btn btn-primary\">Done</a>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }
}
