<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once TIMECLOCK_ROOT . '/functions.php';

final class FunctionsPureTest extends TestCase
{
    public function testTcHashPasswordProducesAVerifiableHash(): void
    {
        $hash = tc_hash_password('correct horse battery staple');

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_verify('correct horse battery staple', $hash));
    }

    public function testTcIsLegacyPasswordHashDetectsCryptOutput(): void
    {
        $this->assertTrue(tc_is_legacy_password_hash('xy.RY2HT1QTc2'));
    }

    public function testTcIsLegacyPasswordHashRejectsModernHash(): void
    {
        $this->assertFalse(tc_is_legacy_password_hash(password_hash('x', PASSWORD_DEFAULT)));
    }

    public function testTcIsLegacyPasswordHashRejectsEmptyOrNull(): void
    {
        $this->assertFalse(tc_is_legacy_password_hash(null));
        $this->assertFalse(tc_is_legacy_password_hash(''));
    }

    public function testTcVerifyPasswordAcceptsCorrectModernHash(): void
    {
        $hash = password_hash('hunter2', PASSWORD_DEFAULT);

        $this->assertTrue(tc_verify_password('hunter2', $hash));
        $this->assertFalse(tc_verify_password('wrong', $hash));
    }

    public function testTcVerifyPasswordAcceptsCorrectLegacyCryptHash(): void
    {
        $legacyHash = crypt('hunter2', 'xy');

        $this->assertTrue(tc_verify_password('hunter2', $legacyHash));
        $this->assertFalse(tc_verify_password('wrong', $legacyHash));
    }

    public function testBtagBuildsAnOpeningTagWithEscapedAttributes(): void
    {
        $this->assertSame(
            '<div class="foo&quot;bar">',
            btag('div', ['class' => 'foo"bar'])
        );
    }

    public function testBtagWithNoAttributes(): void
    {
        $this->assertSame('<div>', btag('div'));
    }

    public function testTagBuildsAFullEscapedElement(): void
    {
        $this->assertSame(
            '<div>Hello &amp; &lt;b&gt;bye&lt;/b&gt;</div>',
            tag('div', 'Hello & <b>bye</b>')
        );
    }

    public function testYesNoBoolParsesYesAndNoCaseInsensitively(): void
    {
        $this->assertTrue(yes_no_bool('YES'));
        $this->assertTrue(yes_no_bool('yes'));
        $this->assertFalse(yes_no_bool('No'));
    }

    public function testYesNoBoolFallsBackToDefaultForOtherValues(): void
    {
        $this->assertFalse(yes_no_bool('maybe'));
        $this->assertTrue(yes_no_bool('maybe', true));
        $this->assertFalse(yes_no_bool(null));
    }

    public function testValueOrNullBlanksBecomeNull(): void
    {
        $this->assertNull(value_or_null(''));
        $this->assertNull(value_or_null('   '));
        $this->assertNull(value_or_null(null));
    }

    public function testValueOrNullPassesThroughNonBlankValues(): void
    {
        $this->assertSame('x', value_or_null('x'));
        $this->assertSame('0', value_or_null('0'));
    }

    public function testHasValue(): void
    {
        $this->assertFalse(has_value(''));
        $this->assertFalse(has_value('   '));
        $this->assertFalse(has_value(null));
        $this->assertTrue(has_value('x'));
        $this->assertTrue(has_value('0'));
    }

    public function testSecsToHoursWithNoRounding(): void
    {
        // 1 hour, 30 minutes -> 1.50
        $this->assertSame('1.50', secsToHours(5400, ''));
    }

    public function testSecsToHoursRoundToNearestQuarterHour(): void
    {
        // 47 minutes rounds up to the 45-minute bucket (0.75) under mode '1'.
        $this->assertSame('0.75', secsToHours(47 * 60, '1'));
    }

    public function testSecsToHoursRoundToNearestThirdHour(): void
    {
        // 40 minutes rounds up to the 35-minute bucket (0.67) under mode '2'.
        $this->assertSame('0.67', secsToHours(40 * 60, '2'));
    }

    public function testIpRangeExactMatch(): void
    {
        $this->assertTrue(ip_range('192.168.1.1', '192.168.1.1'));
        $this->assertFalse(ip_range('192.168.1.1', '192.168.1.2'));
    }

    public function testIpRangeBracketRange(): void
    {
        $this->assertTrue(ip_range('192.168.1.[10-20]', '192.168.1.15'));
        $this->assertFalse(ip_range('192.168.1.[10-20]', '192.168.1.25'));
    }

    public function testIpRangeCidr(): void
    {
        $this->assertTrue(ip_range('192.168.1.0/24', '192.168.1.200'));
        $this->assertFalse(ip_range('192.168.1.0/24', '192.168.2.200'));
    }
}
