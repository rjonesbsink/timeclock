<?php

/**
 * Shared admin sidebar nav, Bootstrap version. The original app duplicates
 * this exact block (as raw <table> markup) into every admin/*.php page
 * individually; this is a single include so pages migrated to the new
 * Bootstrap layout (see issue #40) don't repeat it again themselves. Pages
 * not yet migrated keep using their own inline copy, untouched.
 */

echo "<div class=\"col-md-3 mb-4\">\n";
echo "  <div class=\"list-group list-group-flush small\">\n";

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0\">Users</div>\n";
echo "    <a href=\"useradmin.php\" class=\"list-group-item list-group-item-action bg-transparent\">User Summary</a>\n";
echo "    <a href=\"usercreate.php\" class=\"list-group-item list-group-item-action bg-transparent\">Create New User</a>\n";
echo "    <a href=\"usersearch.php\" class=\"list-group-item list-group-item-action bg-transparent\">User Search</a>\n";

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Offices</div>\n";
echo "    <a href=\"officeadmin.php\" class=\"list-group-item list-group-item-action bg-transparent\">Office Summary</a>\n";
echo "    <a href=\"officecreate.php\" class=\"list-group-item list-group-item-action bg-transparent\">Create New Office</a>\n";

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Groups</div>\n";
echo "    <a href=\"groupadmin.php\" class=\"list-group-item list-group-item-action bg-transparent\">Group Summary</a>\n";
echo "    <a href=\"groupcreate.php\" class=\"list-group-item list-group-item-action bg-transparent\">Create New Group</a>\n";

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">In/Out Status</div>\n";
echo "    <a href=\"statusadmin.php\" class=\"list-group-item list-group-item-action bg-transparent\">Status Summary</a>\n";
echo "    <a href=\"statuscreate.php\" class=\"list-group-item list-group-item-action bg-transparent\">Create Status</a>\n";

echo "    <div class=\"list-group-item bg-transparent fw-bold border-0 pb-0 mt-3\">Miscellaneous</div>\n";
echo "    <a href=\"timeadmin.php\" class=\"list-group-item list-group-item-action bg-transparent\">Add/Edit/Delete Time</a>\n";
echo "    <a href=\"sysedit.php\" class=\"list-group-item list-group-item-action bg-transparent\">Edit System Settings</a>\n";
echo "    <a href=\"dbupgrade.php\" class=\"list-group-item list-group-item-action bg-transparent\">Upgrade Database</a>\n";

echo "  </div>\n";
echo "</div>\n";
