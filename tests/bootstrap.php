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
 */
function require_with_cwd(string $dir, string $file): void
{
    $originalCwd = getcwd();
    chdir($dir);
    try {
        require_once $file;
    } finally {
        chdir($originalCwd);
    }
}
