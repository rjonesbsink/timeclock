<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once TIMECLOCK_ROOT . '/lib/auth.php';

final class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $GLOBALS['use_reports_password'] = 'no';
    }

    public function testReportsLoginRequiredFalseWhenPasswordProtectionDisabled(): void
    {
        $GLOBALS['use_reports_password'] = 'no';

        $this->assertFalse(reports_login_required());
    }

    public function testReportsLoginRequiredTrueWhenProtectedAndNotLoggedIn(): void
    {
        $GLOBALS['use_reports_password'] = 'yes';

        $this->assertTrue(reports_login_required());
    }

    public function testReportsLoginRequiredFalseWhenProtectedAndLoggedIn(): void
    {
        $GLOBALS['use_reports_password'] = 'yes';
        $_SESSION['valid_reports_user'] = 'someuser';

        $this->assertFalse(reports_login_required());
    }

    public function testReportsOrAdminLoginRequiredLetsSysAdminThrough(): void
    {
        $GLOBALS['use_reports_password'] = 'yes';
        $_SESSION['valid_user'] = 'admin';

        $this->assertFalse(reports_or_admin_login_required());
    }

    public function testReportsOrAdminLoginRequiredTrueForAnonymousWhenProtected(): void
    {
        $GLOBALS['use_reports_password'] = 'yes';

        $this->assertTrue(reports_or_admin_login_required());
    }

    public function testCurrentAdminUsernamePrefersValidUser(): void
    {
        $_SESSION['valid_user'] = 'sysadmin';
        $_SESSION['time_admin_valid_user'] = 'timeadmin';

        $this->assertSame('sysadmin', current_admin_username());
    }

    public function testCurrentAdminUsernameFallsBackToTimeAdmin(): void
    {
        $_SESSION['time_admin_valid_user'] = 'timeadmin';

        $this->assertSame('timeadmin', current_admin_username());
    }

    public function testCurrentAdminUsernameEmptyWhenNeitherSet(): void
    {
        $this->assertSame('', current_admin_username());
    }

    public function testPrintLoginRequiredMessageIncludesLoginLink(): void
    {
        ob_start();
        print_login_required_message('../login.php');
        $html = ob_get_clean();

        $this->assertStringContainsString("href='../login.php'", $html);
        $this->assertStringContainsString('PHP Timeclock Administration', $html);
        $this->assertStringNotContainsString('PHP Timeclock Reports', $html);
    }

    public function testPrintLoginRequiredMessageReportsModeChangesHeading(): void
    {
        ob_start();
        print_login_required_message('../login_reports.php', true);
        $html = ob_get_clean();

        $this->assertStringContainsString('PHP Timeclock Reports', $html);
    }

    public function testRequireValidUserDoesNotHaltWhenSessionValid(): void
    {
        $_SESSION['valid_user'] = 'admin';

        ob_start();
        require_valid_user();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testRequireTimeAdminDoesNotHaltForEitherAdminRole(): void
    {
        $_SESSION['time_admin_valid_user'] = 'someone';

        ob_start();
        require_time_admin();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testRequireApplicationContextDoesNotHaltWhenSet(): void
    {
        $_SESSION['application'] = 'entry.php';

        ob_start();
        require_application_context();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
