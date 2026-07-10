<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

require_once TIMECLOCK_ROOT . '/functions.php';

/**
 * Base class for tests that hit a real database. Connects to a dedicated
 * `timeclock_test` database (see sql/create_tables.sql) rather than the
 * developer's timeclock_dev, and skips (not fails) if that database isn't
 * reachable, since these tests are opt-in/local-only and not wired into CI.
 *
 * The app's tables are all MyISAM (no transactions), so there's no
 * begin/rollback isolation available -- each test is responsible for
 * cleaning up the specific rows it inserts, in tearDown().
 */
abstract class DatabaseTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (isset($GLOBALS['___mysqli_ston'])) {
            return;
        }

        $connection = @mysqli_connect('localhost', 'root', '', 'timeclock_test');
        if (!$connection) {
            self::markTestSkipped(
                'timeclock_test database is not reachable: ' . mysqli_connect_error()
                . '. Create it with: mysql -u root -e "CREATE DATABASE timeclock_test" '
                . '&& mysql -u root timeclock_test < sql/create_tables.sql'
            );
        }

        $GLOBALS['___mysqli_ston'] = $connection;
        $GLOBALS['db_prefix'] = '';
    }
}
