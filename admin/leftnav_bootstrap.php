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
 */

$admin_leftnav_current = $admin_leftnav_current ?? '';

echo "<div class=\"col-md-3 mb-4\">\n";
echo "  <div class=\"list-group list-group-flush small\">\n";

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0\">Users</div>\n";
admin_leftnav_link('useradmin.php', 'User Summary', $admin_leftnav_current);
admin_leftnav_link('usercreate.php', 'Create New User', $admin_leftnav_current);
admin_leftnav_link('usersearch.php', 'User Search', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Offices</div>\n";
admin_leftnav_link('officeadmin.php', 'Office Summary', $admin_leftnav_current);
admin_leftnav_link('officecreate.php', 'Create New Office', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Groups</div>\n";
admin_leftnav_link('groupadmin.php', 'Group Summary', $admin_leftnav_current);
admin_leftnav_link('groupcreate.php', 'Create New Group', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">In/Out Status</div>\n";
admin_leftnav_link('statusadmin.php', 'Status Summary', $admin_leftnav_current);
admin_leftnav_link('statuscreate.php', 'Create Status', $admin_leftnav_current);

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Miscellaneous</div>\n";
admin_leftnav_link('timeadmin.php', 'Add/Edit/Delete Time', $admin_leftnav_current);
admin_leftnav_link('sysedit.php', 'Edit System Settings', $admin_leftnav_current);
admin_leftnav_link('dbupgrade.php', 'Upgrade Database', $admin_leftnav_current);

echo "  </div>\n";
echo "</div>\n";
