<?php

/*
 * Shared session bootstrap. Every page that used to call the bare
 * session_start() calls start_secure_session() instead, so session
 * cookies get consistent hardening regardless of what a given server's
 * php.ini happens to default to.
 *
 * No dependency on config.inc.php or the database -- this has to be safe
 * to require from setup.php, which runs before config.inc.php exists.
 */

function start_secure_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    // php:S2092 wants a literal "secure" => true here, but the secure flag
    // below is intentionally derived from the request instead of hardcoded
    // -- see the comment on that line.
    session_set_cookie_params([ // NOSONAR
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        // Lax, not Strict: Strict would drop the cookie on a top-level
        // navigation arriving from an external link (a bookmark, an email
        // link to the app), which would make an already-logged-in user
        // look logged out on their very next click. Lax still blocks the
        // cross-site POST/subresource cases CSRF protection cares about.
        'samesite' => 'Lax',
        // Detected, not hardcoded true: a hardcoded Secure flag would make
        // the browser silently refuse to send the cookie at all on a
        // plain-HTTP deployment (like this local dev server), breaking
        // login instead of hardening it.
        'secure' => $isHttps,
    ]);

    session_start();
}
