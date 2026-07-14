<?php

/**
 * Bootstrap-based drop-in replacement for admin/topmain.php. Same
 * links/session logic as admin/topmain.php -- only the HTML output differs.
 * Used by admin pages migrated to the new Bootstrap layout (see issue #40);
 * admin/topmain.php itself is untouched and still serves every admin page
 * not yet migrated.
 */

if (($dbexists <> "1") || (@$my_dbversion <> $dbversion)) {
    echo "<div class=\"alert alert-danger rounded-0 mb-0 text-center notprint\" role=\"alert\">\n";
    echo "  <strong>Your database is out of date.</strong> Upgrade it via the admin section.\n";
    echo "</div>\n";
}

echo "<nav class=\"navbar navbar-expand-md navbar-dark bg-dark\">\n";
echo "  <div class=\"container-fluid\">\n";

if ($logo == "none") {
    echo "    <a class=\"navbar-brand\" href=\"../index.php\">$app_name</a>\n";
} else {
    echo "    <a class=\"navbar-brand\" href=\"../index.php\"><img src=\"../$logo\" alt=\"$app_name\" height=\"30\"></a>\n";
}

echo "    <button class=\"navbar-toggler\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#topmainNav\"
             aria-controls=\"topmainNav\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">\n";
echo "      <span class=\"navbar-toggler-icon\"></span>\n";
echo "    </button>\n";
echo "    <div class=\"collapse navbar-collapse\" id=\"topmainNav\">\n";
echo "      <ul class=\"navbar-nav ms-auto align-items-md-center\">\n";

if (isset($_SESSION['valid_user'])) {
    $logged_in_user = htmlentities($_SESSION['valid_user']);
    echo "        <li class=\"nav-item\"><span class=\"nav-link text-warning\">logged in as: $logged_in_user</span></li>\n";
} elseif (isset($_SESSION['time_admin_valid_user'])) {
    $logged_in_user = htmlentities($_SESSION['time_admin_valid_user']);
    echo "        <li class=\"nav-item\"><span class=\"nav-link text-danger\">logged in as: $logged_in_user</span></li>\n";
} elseif (isset($_SESSION['valid_reports_user'])) {
    $logged_in_user = htmlentities($_SESSION['valid_reports_user']);
    echo "        <li class=\"nav-item\"><span class=\"nav-link text-info\">logged in as: $logged_in_user</span></li>\n";
}

echo "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"../index.php\">Home</a></li>\n";
echo "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"../login.php\">Administration</a></li>\n";

if ($use_reports_password == "yes") {
    echo "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"../login_reports.php\">Reports</a></li>\n";
} elseif ($use_reports_password == "no") {
    echo "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"../reports/index.php\">Reports</a></li>\n";
}

echo "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"../punchclock/menu.php\">Punchclock</a></li>\n";

if ((isset($_SESSION['valid_user'])) || (isset($_SESSION['time_admin_valid_user'])) || (isset($_SESSION['valid_reports_user']))) {
    echo "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"../logout.php\">Logout</a></li>\n";
}

echo "      </ul>\n";
echo "    </div>\n";
echo "  </div>\n";
echo "</nav>\n";

$todaydate = date('F j, Y');
echo "<div class=\"text-end small text-muted px-3 py-1 notprint\">\n";
if ($date_link == "none") {
    echo "  $todaydate\n";
} else {
    echo "  <a href=\"$date_link\" class=\"text-muted text-decoration-none\">$todaydate</a>\n";
}
if ($use_client_tz == "yes") {
    echo "  &nbsp;&bull;&nbsp;If the times below appear to be an hour off, click <a href=\"../resetcookie.php\">here</a> to reset.\n";
}
echo "</div>\n";
