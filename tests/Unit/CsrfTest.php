<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once TIMECLOCK_ROOT . '/lib/csrf.php';

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        unset($_POST['csrf_token']);
    }

    public function testCsrfTokenGeneratesAndPersistsAToken(): void
    {
        $token = csrf_token();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, csrf_token(), 'calling csrf_token() again must not rotate it');
    }

    public function testCsrfFieldRendersTheCurrentTokenEscaped(): void
    {
        $_SESSION['csrf_token'] = '"><script>alert(1)</script>';

        $field = csrf_field();

        $this->assertStringNotContainsString('<script>', $field);
        $this->assertStringContainsString(
            htmlentities('"><script>alert(1)</script>'),
            $field
        );
    }

    public function testRegenerateCsrfTokenProducesADifferentToken(): void
    {
        $first = csrf_token();
        $second = regenerate_csrf_token();

        $this->assertNotSame($first, $second);
        $this->assertSame($second, $_SESSION['csrf_token']);
    }

    public function testVerifyCsrfTokenTrueWhenSessionAndPostMatch(): void
    {
        $_SESSION['csrf_token'] = 'abc123';
        $_POST['csrf_token'] = 'abc123';

        $this->assertTrue(verify_csrf_token());
    }

    public function testVerifyCsrfTokenFalseWhenTheyDiffer(): void
    {
        $_SESSION['csrf_token'] = 'abc123';
        $_POST['csrf_token'] = 'something-else';

        $this->assertFalse(verify_csrf_token());
    }

    public function testVerifyCsrfTokenFalseWhenPostTokenMissing(): void
    {
        $_SESSION['csrf_token'] = 'abc123';

        $this->assertFalse(verify_csrf_token());
    }

    public function testVerifyCsrfTokenFalseWhenSessionTokenMissing(): void
    {
        $_POST['csrf_token'] = 'abc123';

        $this->assertFalse(verify_csrf_token());
    }

    public function testRequireCsrfTokenDoesNotHaltOrEchoOnAValidToken(): void
    {
        $_SESSION['csrf_token'] = 'abc123';
        $_POST['csrf_token'] = 'abc123';

        ob_start();
        require_csrf_token();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
