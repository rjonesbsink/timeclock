<?php

require_once '../lib/session.php';
start_secure_session();

include_once '../config.inc.php';
include_once 'header_bootstrap.php';
include_once 'topmain_bootstrap.php';
echo "<title>$title - Create Office</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
const FOOTER_PHP = 'footer_bootstrap.php';
const OFFICENAME_PATTERN = "^([[:alnum:]]| |-|_|\.)+$";

require_once '../lib/auth.php';
require_valid_user();
require_once '../lib/csrf.php';

if ($request == 'GET') {
    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'officecreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";
    echo "      <h5><img src='../images/icons/brick_add.png'> Create Office</h5>\n";
    echo "      <form name='form' action='$self' method='post'>\n";
    echo csrf_field() . "\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='post_officename'>Office Name <span class=\"text-danger\">*</span></label>\n";
    echo "          <input type='text' id='post_officename' class=\"form-control\" maxlength='50' name='post_officename'>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label d-block\">Create Groups Within This Office?</label>\n";
    echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_groups' value='1' id='create_groups_y'
                onFocus=\"javascript:form.how_many.disabled=false;form.how_many.style.background='#ffffff';\">
                <label class=\"form-check-label\" for='create_groups_y'>Yes</label></div>\n";
    echo "          <div class=\"form-check form-check-inline\"><input checked type='radio' class=\"form-check-input\" name='create_groups' value='0' id='create_groups_n'
                onFocus=\"javascript:form.how_many.disabled=true;form.how_many.style.background='#eeeeee';\">
                <label class=\"form-check-label\" for='create_groups_n'>No</label></div>\n";
    echo "        </div>\n";
    echo "        <div class=\"mb-3\">\n";
    echo "          <label class=\"form-label\" for='how_many'>How Many?</label>\n";
    echo "          <input disabled type='text' id='how_many' class=\"form-control\" size='2' maxlength='1' name='how_many' style='background:#eeeeee;'>\n";
    echo "        </div>\n";
    echo "        <p class=\"small text-muted\">* required</p>\n";
    echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Office'>Create Office</button>\n";
    echo "        <a href='officeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
    echo "      </form>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
    include_once FOOTER_PHP;
    exit;
} elseif ($request == 'POST') {
    require_csrf_token();

    $post_officename = post_string('post_officename');
    $create_groups = post_string('create_groups');
    @$how_many = $_POST['how_many'];
    // Genuinely absent (create_groups=0, or the first step of the
    // create_groups=1 wizard before group names are collected) must stay
    // distinguishable from "submitted" via isset() below -- default to null
    // rather than [] so a scalar/array-confusion attack is treated the same
    // as "not submitted yet" instead of skipping straight to validation.
    $input_group_name = post_array('input_group_name', null);

    // how_many is only ever a plain text field on the real form -- a crafted
    // how_many[]=1 request would otherwise reach preg_match()/string
    // interpolation below as an array, which is a fatal TypeError on the
    // former and an "Array to string conversion" warning on the latter.
    // Deliberately NOT the same as "not submitted" (the normal, expected
    // case when create_groups is "No"): unsetting it would let a
    // malformed how_many silently fall through to creating the office
    // with no groups even when create_groups=1 was submitted, with no
    // indication anything was wrong. Coerce to a value that's guaranteed
    // to fail the "is this a single digit" checks below instead, so it's
    // rejected the same way any other invalid how_many already is.
    if (isset($how_many) && !is_string($how_many)) {
        $how_many = '';
    }

    echo "<div class=\"container-fluid mt-3\">\n";
    echo "  <div class=\"row\">\n";
    $admin_leftnav_current = 'officecreate.php';
    include_once 'leftnav_bootstrap.php';
    echo "    <div class=\"col-md-9\">\n";

    $post_officename = addslashes($post_officename);

    // begin post validation //

    // check for duplicate officenames //

    $office_name_exists = entity_name_exists("offices", "officename", $post_officename);

    // error checking: check for duplicate names, disallow certain characters for some fields, etc... //

    $string = strstr($post_officename, "\'");
    $string2 = strstr($post_officename, "\"");

    if (
        ($office_name_exists) || (empty($post_officename)) || (!preg_match('/' . OFFICENAME_PATTERN . '/i', $post_officename)) ||
        ((isset($how_many)) && (!preg_match('/' . "^([0-9])$" . '/i', $how_many))) || (@$how_many == '0') || (($create_groups != '1') && (!empty($create_groups))) ||
        (!empty($string)) || (!empty($string2))
    ) {
        if (empty($post_officename)) {
            echo "      <div class=\"alert alert-danger\">An Office Name is required.</div>\n";
        } elseif (!empty($string)) {
            echo "      <div class=\"alert alert-danger\">Apostrohpes are not allowed when creating an Office Name.</div>\n";
        } elseif (!empty($string2)) {
            echo "      <div class=\"alert alert-danger\">Double Quotes are not allowed when creating an Office Name.</div>\n";
        } elseif ($office_name_exists) {
            echo "      <div class=\"alert alert-danger\">Office already exists. Create another office.</div>\n";
        } elseif (!preg_match('/' . OFFICENAME_PATTERN . '/i', $post_officename)) {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, underscores, spaces, and periods are allowed
                    when creating an Office Name.</div>\n";
        } elseif (($create_groups == '1') && (empty($how_many))) {
            echo "      <div class=\"alert alert-danger\">Please input the number of groups you wish to create within this new office.</div>\n";
        } elseif (($create_groups == '1') && ($how_many == '0')) {
            echo "      <div class=\"alert alert-danger\">You have chosen to create groups within this new office. Please input a number
                    other than '0' for 'How Many?'.</div>\n";
        } elseif (!preg_match('/' . "^([0-9])$" . '/i', $how_many)) {
            echo "      <div class=\"alert alert-danger\">Only numeric characters are allowed for an office count.</div>\n";
        } elseif (($create_groups != '1') && (!empty($create_groups))) {
            echo "      <div class=\"alert alert-danger\">Choose \"yes\" or \"no\" to the <i>Create Groups Within This Office</i> question.</div>\n";
        }

        if (!empty($string)) {
            $post_officename = stripslashes($post_officename);
        }
        if (!empty($string2)) {
            $post_officename = stripslashes($post_officename);
        }

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='post_officename'>Office Name <span class=\"text-danger\">*</span></label>\n";
        echo "          <input type='text' id='post_officename' class=\"form-control\" maxlength='50' name='post_officename' value=\""
            . htmlentities($post_officename) . "\">\n";
        echo "        </div>\n";

        if (!empty($string)) {
            $post_officename = addslashes($post_officename);
        }
        if (!empty($string2)) {
            $post_officename = addslashes($post_officename);
        }

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label d-block\">Create Groups Within This Office?</label>\n";
        if ($create_groups == '1') {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_groups' value='1' checked
                    id='create_groups_y' onFocus=\"javascript:form.how_many.disabled=false;form.how_many.style.background='#ffffff';\">
                    <label class=\"form-check-label\" for='create_groups_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_groups' value='0'
                    id='create_groups_n' onFocus=\"javascript:form.how_many.disabled=true;form.how_many.style.background='#eeeeee';\">
                    <label class=\"form-check-label\" for='create_groups_n'>No</label></div>\n";
        } else {
            echo "          <div class=\"form-check form-check-inline\"><input type='radio' class=\"form-check-input\" name='create_groups' value='1'
                    id='create_groups_y' onFocus=\"javascript:form.how_many.disabled=false;form.how_many.style.background='#ffffff';\">
                    <label class=\"form-check-label\" for='create_groups_y'>Yes</label></div>\n";
            echo "          <div class=\"form-check form-check-inline\"><input checked type='radio' class=\"form-check-input\" name='create_groups' value='0'
                    id='create_groups_n' onFocus=\"javascript:form.how_many.disabled=true;form.how_many.style.background='#eeeeee';\">
                    <label class=\"form-check-label\" for='create_groups_n'>No</label></div>\n";
        }
        echo "        </div>\n";

        echo "        <div class=\"mb-3\">\n";
        echo "          <label class=\"form-label\" for='how_many'>How Many?</label>\n";
        $h_how_many = htmlentities($how_many);
        if ($create_groups == '1') {
            echo "          <input type='text' id='how_many' class=\"form-control\" size='2' maxlength='1' name='how_many' value=\"$h_how_many\">\n";
        } else {
            echo "          <input disabled type='text' id='how_many' class=\"form-control\" size='2' maxlength='1' name='how_many'
                    style='background:#eeeeee;' value=\"$h_how_many\">\n";
        }
        echo "        </div>\n";

        echo "        <p class=\"small text-muted\">* required</p>\n";
        echo "        <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Office'>Create Office</button>\n";
        echo "        <a href='officeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }

    // end post validation //

    if (isset($input_group_name)) {
        for ($x = 0; $x < $how_many; $x++) {
            $z = $x + 1;

            // begin post validation //

            if (empty($input_group_name[$z])) {
                $empty_groupname = '1';
            }
            if (!preg_match('/' . OFFICENAME_PATTERN . '/i', $input_group_name[$z])) {
                $evil_groupname = '1';
            }
        }

        @$groupname_array_cnt = count($input_group_name);
        @$unique_groupname_array = array_unique($input_group_name);
        @$unique_groupname_array_cnt = count($unique_groupname_array);

        if ((@$empty_groupname != '1') && (@$evil_groupname != '1') && (@$groupname_array_cnt == @$unique_groupname_array_cnt)) {
            $tmp_officeid = tc_insert_strings("offices", array("officename" => $post_officename));

            for ($x = 0; $x < $how_many; $x++) {
                $y = $x + 1;
                tc_insert_strings("groups", array("groupname" => $input_group_name[$y], "officeid" => $tmp_officeid));
            }

            echo "      <div class=\"alert alert-success\">Office created successfully.</div>\n";
        }

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Office Name:</th><td><input type='hidden' name='post_officename' value=\"" . htmlentities($post_officename) . "\">"
            . htmlentities($post_officename) . "</td></tr>\n";
        echo "        <tr><th>Create Groups Within This Office?</th><td><input type='hidden' name='create_groups' value=\""
            . htmlentities($create_groups) . "\">" . htmlentities($create_groups) . "</td></tr>\n";
        echo "        <tr><th>How Many?</th><td><input type='hidden' name='how_many' value=\"" . htmlentities($how_many) . "\">"
            . htmlentities($how_many) . "</td></tr>\n";
        echo "      </table>\n";

        if (@$empty_groupname == '1') {
            echo "      <div class=\"alert alert-danger\">A Group Name is required.</div>\n";
        } elseif (@$evil_groupname == '1') {
            echo "      <div class=\"alert alert-danger\">Alphanumeric characters, hyphens, underscores, spaces, and periods are allowed
                    when creating a Group Name.</div>\n";
        } elseif (@$groupname_array_cnt != @$unique_groupname_array_cnt) {
            echo "      <div class=\"alert alert-danger\">Duplicate Group Name exists.</div>\n";
        }

        if ((@$empty_groupname != '1') && (@$evil_groupname != '1') && (@$groupname_array_cnt == @$unique_groupname_array_cnt)) {
            if ($how_many == '1') {
                echo "      <div class=\"alert alert-success\">" . htmlentities($how_many) . " group was created within the <b>"
                    . htmlentities($post_officename) . "</b> office successfully.</div>\n";
            } elseif ($how_many > '1') {
                echo "      <div class=\"alert alert-success\">" . htmlentities($how_many) . " groups were created within the <b>"
                    . htmlentities($post_officename) . "</b> office successfully.</div>\n";
            }
        }

        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";

        for ($x = 0; $x < $how_many; $x++) {
            $y = $x + 1;

            if ((@$empty_groupname == '1') || (@$evil_groupname == '1') || (@$groupname_array_cnt != @$unique_groupname_array_cnt)) {
                echo "        <tr><td>$y.</td><td><input type='text' class=\"form-control\" size='25' maxlength='50' name='input_group_name[$y]'
                        value=\"" . htmlentities($input_group_name[$y]) . "\"></td></tr>\n";
            } else {
                echo "        <tr><td>$y.</td><td>" . htmlentities($input_group_name[$y]) . "</td></tr>\n";
            }
        } // end for loop

        echo "      </table>\n";

        if ((@$empty_groupname == '1') || (@$evil_groupname == '1') || (@$groupname_array_cnt != @$unique_groupname_array_cnt)) {
            echo "      <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Office'>Create Office</button>\n";
            echo "      <a href='officeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
            echo "      </form>\n";
            echo "    </div>\n";
            echo "  </div>\n";
            echo "</div>\n";
            include_once FOOTER_PHP;
            exit;
        } else {
            echo "      </form>\n";
            echo "      <a href='officecreate.php' class=\"btn btn-primary\">Done</a>\n";
            echo "    </div>\n";
            echo "  </div>\n";
            echo "</div>\n";
            include_once FOOTER_PHP;
            exit;
        }
    } else {
        if (!isset($how_many)) {
            tc_insert_strings("offices", array("officename" => $post_officename));

            echo "      <div class=\"alert alert-success\">Office created successfully.</div>\n";
        }

        echo "      <form name='form' action='$self' method='post'>\n";
        echo csrf_field() . "\n";
        echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
        echo "        <tr><th>Office Name:</th><td><input type='hidden' name='post_officename' value=\"" . htmlentities($post_officename) . "\">"
            . htmlentities($post_officename) . "</td></tr>\n";

        if ($create_groups == "1") {
            $tmp_create_groups = "Yes";
        } else {
            $tmp_create_groups = "No";
        }
        echo "        <tr><th>Create Groups Within This Office?</th><td><input type='hidden' name='create_groups' value=\""
            . htmlentities($create_groups) . "\">$tmp_create_groups</td></tr>\n";

        if (!isset($how_many)) {
            echo "      </table>\n";
            echo "      </form>\n";
            echo "      <a href='officecreate.php' class=\"btn btn-primary\">Done</a>\n";
            echo "    </div>\n";
            echo "  </div>\n";
            echo "</div>\n";
            include_once FOOTER_PHP;
            exit;
        }

        if (isset($how_many)) {
            echo "        <tr><th>How Many?</th><td><input type='hidden' name='how_many' value=\"" . htmlentities($how_many) . "\">"
                . htmlentities($how_many) . "</td></tr>\n";
            echo "      </table>\n";

            if ($how_many == '1') {
                echo "      <p>You have chosen to create <b>" . htmlentities($how_many) . "</b> group within the <b>"
                    . htmlentities($post_officename) . "</b> office. Please input the group name below.</p>\n";
            } elseif ($how_many > '1') {
                echo "      <p>You have chosen to create <b>" . htmlentities($how_many) . "</b> groups within the <b>"
                    . htmlentities($post_officename) . "</b> office. Please input the group names below.</p>\n";
            }

            echo "      <table class=\"table table-sm table-bordered w-auto\">\n";
            for ($x = 0; $x < $how_many; $x++) {
                $y = $x + 1;
                echo "        <tr><td>$y.</td><td><input type='text' class=\"form-control\" size='25' maxlength='50' name='input_group_name[$y]'></td></tr>\n";
            }
            echo "      </table>\n";
        }

        echo "      <button type='submit' class=\"btn btn-primary\" name='submit' value='Create Office'>Create Office</button>\n";
        echo "      <a href='officeadmin.php' class=\"btn btn-outline-secondary\">Cancel</a>\n";
        echo "      </form>\n";
        echo "    </div>\n";
        echo "  </div>\n";
        echo "</div>\n";
        include_once FOOTER_PHP;
        exit;
    }
}
