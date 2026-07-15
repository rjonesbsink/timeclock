<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Edit Group</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

const WHERE_GROUP_AND_OFFICE_ORDER_BY_EMPFULLNAME = "`groups` = ? and office = ? order by empfullname";
const WHERE_ADMIN_GROUP_AND_OFFICE = "admin = '1' and `groups` = ? and office = ?";
const WHERE_TIME_ADMIN_GROUP_AND_OFFICE = "time_admin = '1' and `groups` = ? and office = ?";
const WHERE_REPORTS_GROUP_AND_OFFICE = "reports = '1' and `groups` = ? and office = ?";
const EMPLOYEE_COLUMNS = "empfullname, displayname, email, `groups`, office, admin, reports, time_admin, disabled";
const FOOTER_PHP = 'footer_bootstrap.php';
const MSG_OFFICE_NOT_DEFINED = "Office name is not defined for this group.\n";
const MSIE3 = "MSIE 3";
const MSIE4 = "MSIE 4";
const MSIE5 = "MSIE 5";
const MSIE6 = "MSIE 6";
const GROUPNAME_PATTERN = "^([[:alnum:]]| |-|_|\.)+$";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

function group_member_table($group, $office, $user_agent)
{
    global $color1, $color2;

    $user_count = tc_select("empfullname", "employees", WHERE_GROUP_AND_OFFICE_ORDER_BY_EMPFULLNAME, array($group, $office));
    @$user_count_rows = mysqli_num_rows($user_count);

    $admin_count = tc_select("empfullname", "employees", WHERE_ADMIN_GROUP_AND_OFFICE, array($group, $office));
    @$admin_count_rows = mysqli_num_rows($admin_count);

    $time_admin_count = tc_select("empfullname", "employees", WHERE_TIME_ADMIN_GROUP_AND_OFFICE, array($group, $office));
    @$time_admin_count_rows = mysqli_num_rows($time_admin_count);

    $reports_count = tc_select("empfullname", "employees", WHERE_REPORTS_GROUP_AND_OFFICE, array($group, $office));
    @$reports_count_rows = mysqli_num_rows($reports_count);

    if ($user_count_rows > '0') {
        $h_group = htmlentities($group);
        $h_office = htmlentities($office);

        echo "      <hr>\n";
        echo "      <h6>Members of $h_group Group in $h_office Office</h6>\n";
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
        echo "          <th>Email Address</th>\n";
        echo "          <th class=\"text-center\">Disabled</th>\n";
        echo "          <th class=\"text-center\">Sys Admin</th>\n";
        echo "          <th class=\"text-center\">Time Admin</th>\n";
        echo "          <th class=\"text-center\">Reports</th>\n";
        echo "          <th class=\"text-center\">Edit</th>\n";
        echo "          <th class=\"text-center\">Chg Pwd</th>\n";
        echo "          <th class=\"text-center\">Delete</th>\n";
        echo "        </tr>\n";

        $row_count = 0;

        $result = tc_select(
            EMPLOYEE_COLUMNS,
            "employees",
            WHERE_GROUP_AND_OFFICE_ORDER_BY_EMPFULLNAME,
            array($group, $office)
        );

        while ($row = mysqli_fetch_array($result)) {
            $empfullname = stripslashes("" . $row['empfullname'] . "");
            $displayname = stripslashes("" . $row['displayname'] . "");
            $h_empfullname = htmlentities($empfullname);
            $officename_qs = urlencode($row['office']);

            $row_count++;
            $row_color = ($row_count % 2) ? $color2 : $color1;

            echo "        <tr style=\"background-color:$row_color;\">\n";
            echo "          <td>$row_count</td>\n";
            echo "          <td><a title=\"Edit User: $h_empfullname\"
                    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">$h_empfullname</a></td>\n";
            echo "          <td>" . htmlentities($displayname) . "</td>\n";
            echo "          <td>" . htmlentities($row["email"]) . "</td>\n";

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

            if ((strpos($user_agent, MSIE6)) || (strpos($user_agent, MSIE5)) || (strpos($user_agent, MSIE4)) || (strpos($user_agent, MSIE3))) {
                echo "          <td class=\"text-center\"><a title=\"Edit User: $h_empfullname\"
                    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">Edit</a></td>\n";
                echo "          <td class=\"text-center\"><a title=\"Change Password: $h_empfullname\"
                    href=\"chngpasswd.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">Chg Pwd</a></td>\n";
                echo "          <td class=\"text-center\"><a title=\"Delete User: $h_empfullname\"
                    href=\"userdelete.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">Delete</a></td></tr>\n";
            } else {
                echo "          <td class=\"text-center\"><a title=\"Edit User: $h_empfullname\"
                    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
                    <img border=0 src='../images/icons/application_edit.png'/></a></td>\n";
                echo "          <td class=\"text-center\"><a title=\"Change Password: $h_empfullname\"
                    href=\"chngpasswd.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
                    <img border=0 src='../images/icons/lock_edit.png'/></a></td>\n";
                echo "          <td class=\"text-center\"><a title=\"Delete User: $h_empfullname\"
                    href=\"userdelete.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
                    <img border=0 src='../images/icons/delete.png'/></a></td></tr>\n";
            }
        }
        echo "      </table>\n";
        echo "      </div>\n";
    }
}

if ($request == 'GET') {
    if ((!isset($_GET['groupname'])) && (!isset($_GET['officename']))) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"alert alert-danger\">\n";
        echo "    <h5>PHP Timeclock Error!</h5>\n";
        echo "    <p>How did you get here?</p>\n";
        echo "    <p>Go back to the <a href='groupadmin.php'>Group Summary</a> page to edit groups.</p>\n";
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
    $admin_leftnav_group_context = array('groupname' => $get_group, 'officename' => $get_office, 'current' => 'groupedit.php');
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
        echo "Group name is not defined for this group.\n";
        exit;
    }

    $result2 = tc_select("*", "employees", "office = ? and `groups` = ?", array($get_office, $get_group));
    @$user_cnt = mysqli_num_rows($result2);

    echo "      <h5><img src='../images/icons/group_edit.png'> Edit Group - $h_get_group</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_groupname'>New Group Name <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' id='post_groupname' class=\"form-control\" maxlength='50' name='post_groupname' value=\"$h_get_group\">\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_officename'>New Parent Office</label>\n";
    echo "          <select id='post_officename' class=\"form-select\" name='post_officename'>\n";
    echo html_options(tc_select("officename", "offices", "1=1 order by officename asc"), $get_office);
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">User Count</label>\n";
    echo "          <input type='hidden' name='user_cnt' value=\"$user_cnt\">\n";
    echo "          <div class=\"form-control-plaintext\">$user_cnt</div>\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <input type='hidden' name='orig_officeid' value=\"$officeid\">\n";
    echo "        <input type='hidden' name='post_groupid' value=\"$groupid\">\n";
    echo "        <input type='hidden' name='get_group' value=\"$h_get_group\">\n";
    echo "        <input type='hidden' name='get_office' value=\"$h_get_office\">\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit Group'>Edit Group</button>\n";
    echo "        <a href='groupadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";

    group_member_table($get_group, $get_office, $user_agent);

    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $post_officename = post_string('post_officename');
    $post_officeid = post_string('post_officeid');
    $orig_officeid = post_string('orig_officeid');
    $post_groupname = post_string('post_groupname');
    $post_groupid = post_string('post_groupid');
    $get_group = post_string('get_group');
    $get_office = post_string('get_office');
    $h_get_group = htmlentities($get_group);
    $h_get_office = htmlentities($get_office);
    $user_cnt = $_POST['user_cnt'];
    $post_groupname = stripslashes($post_groupname);
    $post_groupname = addslashes($post_groupname);

    $string = strstr($post_groupname, "\'");
    $string2 = strstr($post_groupname, "\"");

    // begin post validation //

    if (!empty($get_office)) {
        $result = tc_select("*", "offices", "officename = ?", $get_office);
        while ($row = mysqli_fetch_array($result)) {
            $getoffice = "" . $row['officename'] . "";
        }
    }
    if (!isset($getoffice)) {
        echo "Office is not defined for this user. Go back and associate this user with an office.\n";
        exit;
    }

    if (!empty($get_group)) {
        $result = tc_select("*", "groups", "groupname = ?", $get_group);
        while ($row = mysqli_fetch_array($result)) {
            $getgroup = "" . $row['groupname'] . "";
        }
    }
    if (!isset($getgroup)) {
        echo "Group is not defined for this user. Go back and associate this user with a group.\n";
        exit;
    }

    if (!empty($post_officename)) {
        $result = tc_select("*", "offices", "officename = ?", $post_officename);
        while ($row = mysqli_fetch_array($result)) {
            $officename = "" . $row['officename'] . "";
            $tmp_officeid = "" . $row['officeid'] . "";
        }
    }
    if (!isset($officename)) {
        echo MSG_OFFICE_NOT_DEFINED;
        exit;
    }

    if (!empty($post_officeid)) {
        $result = tc_select("*", "offices", "officeid = ?", $post_officeid);
        while ($row = mysqli_fetch_array($result)) {
            $post_officeid = "" . $row['officeid'] . "";
            $post_officeid = $tmp_officeid;
        }
        if (!isset($post_officeid)) {
            echo "Office id is not defined for this group.\n";
            exit;
        }
    } else {
        $post_officeid = $tmp_officeid;
    }

    if (!empty($orig_officeid)) {
        $result = tc_select("*", "offices", "officeid = ?", $orig_officeid);
        while ($row = mysqli_fetch_array($result)) {
            $origofficeid = "" . $row['officeid'] . "";
        }
    }
    if (!isset($origofficeid)) {
        echo MSG_OFFICE_NOT_DEFINED;
        exit;
    }

    if (!empty($post_groupid)) {
        $result = tc_select("*", "groups", "groupid = ?", $post_groupid);
        while ($row = mysqli_fetch_array($result)) {
            $groupid = "" . $row['groupid'] . "";
        }
    }
    if (!isset($groupid)) {
        echo "Group id is not defined for this group.\n";
        exit;
    }

    $result = tc_select("*", "employees", "office = ? and `groups` = ?", array($get_office, $get_group));
    @$tmp_user_cnt = mysqli_num_rows($result);

    if ($user_cnt != $tmp_user_cnt) {
        echo "Posted user count does not equal actual user count for this group.\n";
        exit;
    }

    $group_name_exists = !(($post_groupname === $get_group) && ($post_officeid === $orig_officeid))
        && entity_name_exists("groups", "groupname", $post_groupname, "officeid = ?", array($post_officeid));

    if (
        (empty($post_groupname)) || (!preg_match('/' . GROUPNAME_PATTERN . '/i', $post_groupname)) || (!empty($string)) ||
        (!empty($string2)) || ($group_name_exists)
    ) {
        $evil_group = '1';
    }

    // end post validation //

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    // admin_leftnav_group_context needs raw (unescaped) values -- see the
    // doc-comment in leftnav_bootstrap.php. Points at the not-yet-renamed
    // group while redisplaying validation errors, or the newly-renamed
    // group once the update below has actually happened.
    if (isset($evil_group)) {
        $admin_leftnav_group_context = array('groupname' => $get_group, 'officename' => $get_office, 'current' => 'groupedit.php');
    } else {
        $admin_leftnav_group_context = array('groupname' => $post_groupname, 'officename' => $post_officename, 'current' => 'groupedit.php');
    }
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    if (isset($evil_group)) {
        echo "      <h5><img src='../images/icons/group_edit.png'> Edit Group - $h_get_group</h5>\n";

        if (empty($post_groupname)) {
            echo "      <div class=\"alert alert-danger\">A Group Name is required.</div>\n";
        } elseif (!empty($string)) {
            echo "      <div class=\"alert alert-danger\">Apostrohpes are not allowed when editing a Group Name.</div>\n";
        } elseif (!empty($string2)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed when editing a Group Name.</div>\n";
        } elseif ($group_name_exists) {
            echo "      <div class=\"alert alert-danger\">This combination of groupname and officename already exist. Please choose another
                    groupname and/or officename.</div>\n";
        } elseif (!preg_match('/' . GROUPNAME_PATTERN . '/i', $post_groupname)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, underscores, spaces, and periods are allowed
                    when creating a Group Name.</div>\n";
        }

        if (!empty($string)) {
            $post_groupname = stripslashes($post_groupname);
        }
        if (!empty($string2)) {
            $post_groupname = stripslashes($post_groupname);
        }

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='post_groupname'>New Group Name <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' id='post_groupname' class=\"form-control\" maxlength='50' name='post_groupname' value=\""
            . htmlentities($post_groupname) . "\">\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='post_officename'>New Parent Office</label>\n";
        echo "          <select id='post_officename' class=\"form-select\" name='post_officename'>\n";
        echo html_options(tc_select("officename", "offices", "1=1 order by officename asc"), $post_officename);
        echo "          </select>\n";
        echo "        </div>\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\">User Count</label>\n";
        echo "          <input type='hidden' name='user_cnt' value=\"$user_cnt\">\n";
        echo "          <div class=\"form-control-plaintext\">$user_cnt</div>\n";
        echo "        </div>\n";
        echo "        <p class=\"small text-muted\">* required</p>\n";
        echo "        <input type='hidden' name='orig_officeid' value=\"$orig_officeid\">\n";
        echo "        <input type='hidden' name='post_officeid' value=\"$post_officeid\">\n";
        echo "        <input type='hidden' name='post_groupid' value=\"$post_groupid\">\n";
        echo "        <input type='hidden' name='get_group' value=\"$h_get_group\">\n";
        echo "        <input type='hidden' name='get_office' value=\"$h_get_office\">\n";
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Edit Group'>Edit Group</button>\n";
        echo "        <a href='groupadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";

        group_member_table($get_group, $get_office, $user_agent);

        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    } else {
        tc_update_strings(
            "employees",
            array("groups" => $post_groupname, "office" => $post_officename),
            "`groups` = ? and office = ?",
            array($get_group, $get_office)
        );

        tc_update_strings(
            "groups",
            array("groupname" => $post_groupname, "officeid" => $post_officeid),
            "groupname = ? and officeid = ?",
            array($get_group, $orig_officeid)
        );

        $h_post_groupname = htmlentities($post_groupname);
        $h_post_officename = htmlentities($post_officename);

        echo "      <div class=\"alert alert-success\">Group properties updated successfully.</div>\n";
        echo "      <h5><img src='../images/icons/group_edit.png'> Edit Group - $h_get_group</h5>\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>New Group Name:</th><td>$h_post_groupname</td></tr>\n";
        echo "        <tr><th>New Parent Office:</th><td>$h_post_officename</td></tr>\n";
        echo "        <tr><th>User Count:</th><td>$user_cnt</td></tr>\n";
        echo "      </table>\n";
        echo "      <a href='groupadmin.php' class=\"btn btn-primary\">Done</a>\n";

        group_member_table($post_groupname, $post_officename, $user_agent);

        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }
}
