<?php

require_once '../lib/session.php';
start_secure_session();

$self = $_SERVER['PHP_SELF'];
$request = $_SERVER['REQUEST_METHOD'];

include '../config.inc.php';
require_once '../lib/auth.php';

if (reports_login_required()) {
    echo "<title>$title</title>\n";
    include '../admin/header.php';
    include 'topmain.php';
    print_login_required_message('../login_reports.php', true);
    exit;
}

include '../admin/header.php';

if ($use_reports_password == "yes") {
    include 'topmain.php';
} else {
    include 'topmain.php';
}
echo "<title>$title - Reports</title>\n";

echo "<table width=100% height=89% border=0 cellpadding=0 cellspacing=1>\n";
echo "  <tr class=right_main_text height=40><td align=center class=title_underline style='color:#853d27;'>Run Reports</td></tr>\n";
echo "  <tr class=right_main_text height=25>\n";
echo "    <td align=center valign=top>&#8226;&nbsp;<a class=admin_headings href='timerpt.php'>Daily Time Report</a>&nbsp;&#8226;</td></tr>\n";
echo "  <tr class=right_main_text height=25>\n";
echo "    <td align=center valign=top>&#8226;&nbsp;<a class=admin_headings href='total_hours.php'>Hours Worked Report</a>&nbsp;&#8226;</td></tr>\n";
echo "  <tr class=right_main_text height=92%>\n";
echo "    <td align=center valign=top>&#8226;&nbsp;<a class=admin_headings href='audit.php'>Audit Log</a>&nbsp;&#8226;</td></tr>\n";
include '../footer.php';
