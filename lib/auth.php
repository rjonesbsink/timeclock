<?php

/*
 * Shared auth-guard helpers, replacing the copy-pasted $_SESSION checks that
 * used to be scattered across admin/, reports/, and punchclock/.
 *
 * These cover the admin/reports/time-admin identity checks and the
 * $_SESSION['application'] direct-access guard. The punchclock per-employee
 * "authenticated" flow is deliberately NOT covered here: each of its ~6 call
 * sites has its own admin-bypass scope and failure handling (redirect vs.
 * die() vs. inline password form), so folding it into one helper would risk
 * changing behavior rather than just deduplicating it.
 */

function print_login_required_message($login_href, $reports_mode = false, $table_style = '')
{
    $heading = $reports_mode ? 'PHP Timeclock Reports' : 'PHP Timeclock Administration';
    $style_attr = $table_style ? " style=\"$table_style\"" : '';
    echo "<table width=100% border=0 cellpadding=7 cellspacing=1$style_attr>\n";
    echo "  <tr class=right_main_text><td height=10 align=center valign=top scope=row class=title_underline>$heading</td></tr>\n";
    echo "  <tr class=right_main_text>\n";
    echo "    <td align=center valign=top scope=row>\n";
    echo "      <table width=200 border=0 cellpadding=5 cellspacing=0>\n";
    echo "        <tr class=right_main_text><td align=center>You are not presently logged in, or do not have permission to view this page.</td></tr>\n";
    echo "        <tr class=right_main_text><td align=center>Click <a class=admin_headings href='$login_href'><u>here</u></a> to login.</td></tr>\n";
    echo "      </table><br /></td></tr></table>\n";
}

// Sys admin only. Assumes header.php/topmain.php were already included
// (true at every admin/*.php call site), so it only needs to print the
// inner message and exit.
function require_valid_user($login_href = '../login.php')
{
    if (!isset($_SESSION['valid_user'])) {
        print_login_required_message($login_href);
        exit;
    }
}

// Sys admin or time admin.
function require_time_admin($login_href = '../login.php')
{
    if (!isset($_SESSION['valid_user']) && !isset($_SESSION['time_admin_valid_user'])) {
        print_login_required_message($login_href);
        exit;
    }
}

// True if the reports section is password-protected and the current session
// isn't a reports user. Callers still need to include their own
// header/topmain before printing the message, since those must resolve
// relative to the CALLING file's directory (reports/), not this file's.
function reports_login_required()
{
    global $use_reports_password;

    return $use_reports_password == "yes" && !isset($_SESSION['valid_reports_user']);
}

// Same, but also lets a full sys admin through (punchclock/export.php's case).
function reports_or_admin_login_required()
{
    global $use_reports_password;

    return $use_reports_password == "yes" && !isset($_SESSION['valid_reports_user']) && !isset($_SESSION['valid_user']);
}

// $_SESSION['application'] is a direct-access guard, not an identity check:
// entry pages (login.php, entry.php, punchclock.php, ...) set it, and
// AJAX/fragment pages that should only be reached from those entry pages
// check it. Existing call sites either die() or redirect on failure.
function require_application_context($redirect_to = null)
{
    if (!isset($_SESSION['application'])) {
        if ($redirect_to) {
            header("Location:$redirect_to");
            exit;
        }
        die("Invalid invocation.");
    }
}

// The admin username to attribute an action to (for audit logging),
// whichever of the two admin session roles is active.
function current_admin_username()
{
    if (isset($_SESSION['valid_user'])) {
        return $_SESSION['valid_user'];
    }
    if (isset($_SESSION['time_admin_valid_user'])) {
        return $_SESSION['time_admin_valid_user'];
    }

    return "";
}
