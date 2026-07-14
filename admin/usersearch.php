<?php

require_once '../lib/session.php';
start_secure_session();

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
const FOOTER_PHP = 'footer_bootstrap.php';
const USERNAME_PATTERN = "^([[:alnum:]]| |-|'|,)+$";

include_once '../config.inc.php';
if ($request !== 'POST') {
    include_once 'header_get_bootstrap.php';
    include_once 'topmain_bootstrap.php';
}
echo "<title>$title - User Search</title>\n";

require_once '../lib/auth.php';
require_valid_user();

if ($request !== 'POST') {
    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'usersearch.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <h5><img src='../images/icons/magnifier.png'> Search for User</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Username</label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='50' name='post_username'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Display Name</label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='50' name='display_name'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Email Address</label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='75' name='email_addy'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Barcode</label>\n";
    echo "          <input type='text' class=\"form-control\" maxlength='75' name='barcode'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Office</label>\n";
    echo "          <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\">Group</label>\n";
    echo "          <select class=\"form-select\" name='group_name'>\n";
    echo "          </select>\n";
    echo "        </div>\n";
    echo "        <p><a href=\"usersearch.php\">reset form</a></p>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Search'>Search</button>\n";
    echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    include_once 'header_post_bootstrap.php';
    include_once 'topmain_bootstrap.php';

    $post_username = post_string('post_username');
    $display_name = post_string('display_name');
    $email_addy = post_string('email_addy');
    $barcode = post_string('barcode');
    $office_name = post_string('office_name');
    $group_name = post_string('group_name');

    // begin post validation //

    if (
        (!preg_match('/' . USERNAME_PATTERN . '/i', $post_username)) || (!preg_match('/' . USERNAME_PATTERN . '/i', $display_name)) ||
        (!preg_match('/' . "^[[:alnum:]_.@-]+$" . '/i', $email_addy))
    ) {
        echo "<div class=\"container-fluid mt-3\">\n";
        echo "  <div class=\"row\">\n";
        $admin_leftnav_current = 'usersearch.php';
        include_once 'leftnav_bootstrap.php';
        echo "    <div class=\"col-md-9\">\n";

        if (!preg_match('/' . USERNAME_PATTERN . '/i', $post_username) && $post_username != "") {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, apostrophes, commas, and spaces are allowed
                    when searching for a Username.</div>\n";
            $evil_input = "1";
        }
        if (!preg_match('/^[[:alnum:]\s\-\',]+$/i', $display_name) && $display_name != "") {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, apostrophes, commas, and spaces are allowed
                    when searching for a Display Name.</div>\n";
            $evil_input = "1";
        }
        if (!preg_match('/^[[:alnum:]_.@-]+$/', $email_addy) && $email_addy != "") {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, underscores, periods, and hyphens are allowed
                    when searching for an Email Address.</div>\n";
            $evil_input = "1";
        }
        if (!(has_value($post_username) || has_value($display_name) || has_value($email_addy) || has_value($barcode))) {
            echo "      <div class=\"alert alert-danger\">A Username, Display Name, Email Address, or Barcode is required.</div>\n";
            $evil_input = "1";
        }

        if (
            has_value($office_name)
            and is_null(tc_select_value("officename", "offices", "officename = ?", $office_name))
        ) {
            echo "Office is not defined.\n";
            exit;
        }

        if (
            has_value($group_name)
            and is_null(tc_select_value("groupname", "groups", "groupname = ?", $group_name))
        ) {
            echo "Group is not defined.\n";
            exit;
        }

        // end post validation //

        if (isset($evil_input)) {
            $h_post_username = htmlentities($post_username);
            $h_display_name = htmlentities($display_name);
            $h_email_addy = htmlentities($email_addy);
            $h_barcode = htmlentities($barcode);

            echo "      <h5><img src='../images/icons/magnifier.png'> Search for User</h5>\n";
            echo "      <form name='form' action='$self' method='post'>\n";
            echo "        <div class=\"mb-3\">\n";
            echo "          <label class=\"form-label\">Username</label>\n";
            echo "          <input type='text' class=\"form-control\" maxlength='50' name='post_username' value='$h_post_username'>\n";
            echo "        </div>\n";
            echo "        <div class=\"mb-3\">\n";
            echo "          <label class=\"form-label\">Display Name</label>\n";
            echo "          <input type='text' class=\"form-control\" maxlength='50' name='display_name' value='$h_display_name'>\n";
            echo "        </div>\n";
            echo "        <div class=\"mb-3\">\n";
            echo "          <label class=\"form-label\">Email Address</label>\n";
            echo "          <input type='text' class=\"form-control\" maxlength='75' name='email_addy' value='$h_email_addy'>\n";
            echo "        </div>\n";
            echo "        <div class=\"mb-3\">\n";
            echo "          <label class=\"form-label\">Barcode</label>\n";
            echo "          <input type='text' class=\"form-control\" maxlength='75' name='barcode' value='$h_barcode'>\n";
            echo "        </div>\n";
            echo "        <div class=\"mb-3\">\n";
            echo "          <label class=\"form-label\">Office</label>\n";
            echo "          <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
            echo "          </select>\n";
            echo "        </div>\n";
            echo "        <div class=\"mb-3\">\n";
            echo "          <label class=\"form-label\">Group</label>\n";
            echo "          <select class=\"form-select\" name='group_name' onfocus='group_names();'>\n";
            echo "            <option selected>" . htmlentities($group_name) . "</option>\n";
            echo "          </select>\n";
            echo "        </div>\n";
            echo "        <p><a href=\"usersearch.php\">reset form</a></p>\n";
            echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Search'>Search</button>\n";
            echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
            echo "      </form>\n";
            echo "    </div>\n";
            echo "  </div>\n";
            echo "</div>\n";
            include_once FOOTER_PHP;
            exit;
        } else {
            $query_where = array();
            $query_params = array();
            $tmp_var = array();

            if (has_value($post_username)) {
                $tmp_var[] = "'" . htmlentities($post_username) . "' in Username";
                $query_where[] = "empfullname LIKE ?";
                $query_params[] = "%" . $post_username . "%";
            }

            if (has_value($display_name)) {
                $tmp_var[] = "'" . htmlentities($display_name) . "' in Display Name";
                $query_where[] = "displayname LIKE ?";
                $query_params[] = "%" . $display_name . "%";
            }

            if (has_value($email_addy)) {
                $tmp_var[] = "'" . htmlentities($email_addy) . "' in Email Address";
                $query_where[] = "email LIKE ?";
                $query_params[] = "%" . $email_addy . "%";
            }

            if (has_value($barcode)) {
                $tmp_var[] = "'" . htmlentities($barcode) . "' in Barcode";
                $query_where[] = "barcode LIKE ?";
                $query_params[] = "%" . $barcode . "%";
            }

            if (has_value($office_name)) {
                $query_where[] = "office = ?";
                $query_params[] = $office_name;

                if (has_value($group_name)) {
                    $query_where[] = "`groups` = ?";
                    $query_params[] = $group_name;
                }
            }

            $tmp_var = implode(" AND ", $tmp_var);
            $row_count = "0";
            $result4 = tc_select("*", "employees", implode(" AND ", $query_where) . " ORDER BY empfullname", $query_params);

            while ($row = mysqli_fetch_array($result4)) {
                $row_count++;

                if ($row_count == "1") {
                    echo "      <h5>User Search Summary</h5>\n";
                    echo "      <p class=\"small text-muted\">Search Results for $tmp_var</p>\n";
                    echo "      <div class=\"table-responsive\">\n";
                    echo "      <table class=\"table table-sm table-bordered align-middle\">\n";
                    echo "        <tr>\n";
                    echo "          <th>&nbsp;</th>\n";
                    echo "          <th>Username</th>\n";
                    echo "          <th>Display Name</th>\n";
                    echo "          <th>Office</th>\n";
                    echo "          <th>Group</th>\n";
                    echo "          <th class=\"text-center\">Disabled</th>\n";
                    echo "          <th class=\"text-center\">Sys Admin</th>\n";
                    echo "          <th class=\"text-center\">Time Admin</th>\n";
                    echo "          <th class=\"text-center\">Reports</th>\n";
                    echo "          <th class=\"text-center\">Edit</th>\n";
                    echo "          <th class=\"text-center\">Chg Pwd</th>\n";
                    echo "          <th class=\"text-center\">Delete</th>\n";
                    echo "        </tr>\n";
                }

                $row_color = ($row_count % 2) ? $color2 : $color1;
                $empfullname = "" . $row['empfullname'] . "";
                $displayname = "" . $row['displayname'] . "";

                $h_empfullname = htmlentities($empfullname);
                $h_office = htmlentities($row['office']);
                $officename_qs = urlencode($row['office']);

                echo "        <tr style=\"background-color:$row_color;\">\n";
                echo "          <td>$row_count</td>\n";
                echo "          <td><a title=\"Edit User: $h_empfullname\"
                    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">$h_empfullname</a></td>\n";
                echo "          <td>" . htmlentities($displayname) . "</td>\n";
                echo "          <td>$h_office</td>\n";
                echo "          <td>" . htmlentities($row['groups']) . "</td>\n";

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

                echo "          <td class=\"text-center\"><a title=\"Edit User: $h_empfullname\"
                    href=\"useredit.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
                    <img border=0 src='../images/icons/application_edit.png'/></a></td>\n";
                echo "          <td class=\"text-center\"><a title=\"Change Password: $h_empfullname\"
                    href=\"chngpasswd.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
                    <img border=0 src='../images/icons/lock_edit.png'/></a></td>\n";
                echo "          <td class=\"text-center\"><a title=\"Delete User: $h_empfullname\"
                    href=\"userdelete.php?username=" . urlencode($empfullname) . "&officename=$officename_qs\">
                    <img border=0 src='../images/icons/delete.png'/></a></td>\n";
                echo "        </tr>\n";
            }
            ((mysqli_free_result($result4) || (is_object($result4) && (get_class($result4) == "mysqli_result"))) ? true : false);

            if ($row_count == "0") {
                echo "      <div class=\"alert alert-danger\">A user was not found matching your criteria. Please try again.</div>\n";
                echo "      <form name='form' action='$self' method='post'>\n";
                echo "        <div class=\"mb-3\">\n";
                echo "          <label class=\"form-label\">Username</label>\n";
                echo "          <input type='text' class=\"form-control\" maxlength='50' name='post_username' value=\"" . htmlentities($post_username) . "\">\n";
                echo "        </div>\n";
                echo "        <div class=\"mb-3\">\n";
                echo "          <label class=\"form-label\">Display Name</label>\n";
                echo "          <input type='text' class=\"form-control\" maxlength='50' name='display_name' value=\"" . htmlentities($display_name) . "\">\n";
                echo "        </div>\n";
                echo "        <div class=\"mb-3\">\n";
                echo "          <label class=\"form-label\">Email Address</label>\n";
                echo "          <input type='text' class=\"form-control\" maxlength='75' name='email_addy' value=\"" . htmlentities($email_addy) . "\">\n";
                echo "        </div>\n";
                echo "        <div class=\"mb-3\">\n";
                echo "          <label class=\"form-label\">Barcode</label>\n";
                echo "          <input type='text' class=\"form-control\" maxlength='75' name='barcode' value=\"" . htmlentities($barcode) . "\">\n";
                echo "        </div>\n";
                echo "        <div class=\"mb-3\">\n";
                echo "          <label class=\"form-label\">Office</label>\n";
                echo "          <select class=\"form-select\" name='office_name' onchange='group_names();'>\n";
                echo "          </select>\n";
                echo "        </div>\n";
                echo "        <div class=\"mb-3\">\n";
                echo "          <label class=\"form-label\">Group</label>\n";
                echo "          <select class=\"form-select\" name='group_name' onfocus='group_names();'>\n";
                echo "            <option selected>" . htmlentities($group_name) . "</option>\n";
                echo "          </select>\n";
                echo "        </div>\n";
                echo "        <p><a href=\"usersearch.php\">reset form</a></p>\n";
                echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Search'>Search</button>\n";
                echo "        <a href='useradmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
                echo "      </form>\n";
                echo "    </div>\n";
                echo "  </div>\n";
                echo "</div>\n";
                include_once FOOTER_PHP;
                exit;
            } else {
                echo "      </table>\n";
                echo "      </div>\n";
                echo "    </div>\n";
                echo "  </div>\n";
                echo "</div>\n";
                include_once FOOTER_PHP;
                exit;
            }
        }
    }
}
