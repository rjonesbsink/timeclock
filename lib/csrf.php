<?php

/*
 * Shared CSRF token helpers. Every state-changing POST form gets a hidden
 * csrf_token field (via csrf_field()) when rendered, and the corresponding
 * POST handler calls verify_csrf_token() (or require_csrf_token(), for
 * full-page callers) before doing anything else.
 *
 * One token per session, generated on first use. Login flows call
 * regenerate_csrf_token() on successful authentication so a token seen
 * before login can't be replayed against the authenticated session.
 */

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = htmlentities(csrf_token());

    return "<input type='hidden' name='csrf_token' value='$token'>";
}

function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $_SESSION['csrf_token'];
}

function verify_csrf_token() {
    return isset($_SESSION['csrf_token'])
        && isset($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// Full-page failure for admin/reports-style pages: print a standalone
// error and stop. AJAX/fragment callers should call verify_csrf_token()
// directly and render their own failure using their existing convention
// (e.g. die(error_msg(...))) instead of this.
function require_csrf_token() {
    if (!verify_csrf_token()) {
        echo "<table width=100% border=0 cellpadding=7 cellspacing=1>\n";
        echo "  <tr class=right_main_text><td height=10 align=center valign=top scope=row class=title_underline>PHP Timeclock Error!</td></tr>\n";
        echo "  <tr class=right_main_text>\n";
        echo "    <td align=center valign=top scope=row>\n";
        echo "      <table width=300 border=0 cellpadding=5 cellspacing=0>\n";
        echo "        <tr class=right_main_text><td align=center>Your session has expired or this request could not be verified. Please go back and try again.</td></tr>\n";
        echo "      </table><br /></td></tr></table>\n";
        exit;
    }
}
