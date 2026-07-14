<?php

/**
 * Shared admin sidebar nav, Bootstrap version. The original app duplicates
 * this exact block (as raw <table> markup) into every admin/*.php page
 * individually; this is a single include so pages migrated to the new
 * Bootstrap layout (see issue #40) don't repeat it again themselves. Pages
 * not yet migrated keep using their own inline copy, untouched.
 *
 * Set $admin_leftnav_current to a link's target filename (e.g.
 * 'useradmin.php') before including this file to highlight that entry,
 * matching the original's per-page class=current_left_rows treatment.
 *
 * Pages that operate on one specific user (useredit.php, chngpasswd.php,
 * userdelete.php) also show 3 indented sub-links -- Edit User/Change
 * Password/Delete User -- between "User Summary" and "Create New User".
 * Set $admin_leftnav_user_context to an array with 'username', 'officename',
 * and 'current' (one of 'useredit.php'/'chngpasswd.php'/'userdelete.php')
 * before including this file to show them, matching the original's
 * class=current_left_rows_indent/left_rows_indent treatment.
 */

$admin_leftnav_current = $admin_leftnav_current ?? '';
$admin_leftnav_user_context = $admin_leftnav_user_context ?? null;

echo "<div class=\"col-md-3 mb-4\">\n";
echo "  <div class=\"list-group list-group-flush small\">\n";

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0\">Users</div>\n";
echo admin_leftnav_link('useradmin.php', 'User Summary', $admin_leftnav_current);

if ($admin_leftnav_user_context) {
    $u = urlencode($admin_leftnav_user_context['username']);
    $o = urlencode($admin_leftnav_user_context['officename']);
    $current_sub = $admin_leftnav_user_context['current'];
    echo admin_leftnav_link("useredit.php?username=$u&officename=$o", '→ Edit User', $current_sub === 'useredit.php' ? "useredit.php?username=$u&officename=$o" : '');
    echo admin_leftnav_link("chngpasswd.php?username=$u&officename=$o", '→ Change Password', $current_sub === 'chngpasswd.php' ? "chngpasswd.php?username=$u&officename=$o" : '');
    echo admin_leftnav_link("userdelete.php?username=$u&officename=$o", '→ Delete User', $current_sub === 'userdelete.php' ? "userdelete.php?username=$u&officename=$o" : '');
}

echo admin_leftnav_link('usercreate.php', 'Create New User', $admin_leftnav_current);
echo admin_leftnav_link('usersearch.php', 'User Search', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Offices</div>\n";
echo admin_leftnav_link('officeadmin.php', 'Office Summary', $admin_leftnav_current);
echo admin_leftnav_link('officecreate.php', 'Create New Office', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Groups</div>\n";
echo admin_leftnav_link('groupadmin.php', 'Group Summary', $admin_leftnav_current);
echo admin_leftnav_link('groupcreate.php', 'Create New Group', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">In/Out Status</div>\n";
echo admin_leftnav_link('statusadmin.php', 'Status Summary', $admin_leftnav_current);
echo admin_leftnav_link('statuscreate.php', 'Create Status', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Miscellaneous</div>\n";
echo admin_leftnav_link('timeadmin.php', 'Add/Edit/Delete Time', $admin_leftnav_current);
echo admin_leftnav_link('sysedit.php', 'Edit System Settings', $admin_leftnav_current);
echo admin_leftnav_link('dbupgrade.php', 'Upgrade Database', $admin_leftnav_current);

echo "  </div>\n";
echo "</div>\n";
