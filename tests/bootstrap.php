<?php

require __DIR__ . '/../vendor/autoload.php';

define('TIMECLOCK_ROOT', dirname(__DIR__));

/*
 * Several library files use bare relative require/include paths (e.g.
 * require_once 'config.inc.php') that PHP resolves against include_path
 * (which contains ".", the current working directory) before falling back
 * to the including file's own directory. The real app is served with CWD
 * set to each script's own directory (PHP's built-in server behavior), so
 * anything requiring one of those files from a different CWD needs to
 * replicate that.
 *
 * The require itself must happen at the CALLER's top-level (file) scope,
 * not inside a helper function -- otherwise any config globals the
 * included chain sets (e.g. punchclock/config.inc.php's $default_in_or_out)
 * end up scoped to that helper function instead of $GLOBALS, and vanish
 * the moment it returns. So this only flips the CWD; call chdir_restore()
 * with its return value once your own top-level require_once is done.
 */
function chdir_for_require(string $dir): string
{
    $originalCwd = getcwd();
    if (!chdir($dir)) {
        throw new \RuntimeException("chdir_for_require: could not chdir to $dir");
    }

    return $originalCwd;
}

function chdir_restore(string $originalCwd): void
{
    chdir($originalCwd);
}
