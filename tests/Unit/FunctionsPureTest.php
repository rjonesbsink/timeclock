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

    protected function tearDown(): void
    {
        unset($_POST['zztest_key'], $_GET['zztest_key'], $_REQUEST['zztest_key']);
    }

    public function testPostStringPassesThroughAStringValue(): void
    {
        $_POST['zztest_key'] = 'hello';

        $this->assertSame('hello', post_string('zztest_key'));
    }

    public function testPostStringFallsBackToDefaultWhenMissing(): void
    {
        $this->assertSame('', post_string('zztest_key'));
        $this->assertSame('fallback', post_string('zztest_key', 'fallback'));
    }

    public function testPostStringFallsBackToDefaultWhenSubmittedAsAnArray(): void
    {
        // The actual bug this exists to prevent: a request can send
        // zztest_key[]=1 instead of zztest_key=1, and PHP happily
        // populates $_POST['zztest_key'] with an array. Passing that into
        // any string-only function (preg_match, stripslashes, etc.) is a
        // fatal TypeError under PHP 8.
        $_POST['zztest_key'] = ['1', '2'];

        $this->assertSame('', post_string('zztest_key'));
        $this->assertSame('fallback', post_string('zztest_key', 'fallback'));
    }

    public function testGetStringPassesThroughAStringValue(): void
    {
        $_GET['zztest_key'] = 'hello';

        $this->assertSame('hello', get_string('zztest_key'));
    }

    public function testGetStringFallsBackToDefaultWhenMissing(): void
    {
        $this->assertSame('', get_string('zztest_key'));
        $this->assertSame('fallback', get_string('zztest_key', 'fallback'));
    }

    public function testGetStringFallsBackToDefaultWhenSubmittedAsAnArray(): void
    {
        $_GET['zztest_key'] = ['1', '2'];

        $this->assertSame('', get_string('zztest_key'));
    }

    public function testRequestStringPassesThroughAStringValue(): void
    {
        $_REQUEST['zztest_key'] = 'hello';

        $this->assertSame('hello', request_string('zztest_key'));
    }

    public function testRequestStringFallsBackToDefaultWhenMissing(): void
    {
        $this->assertSame('', request_string('zztest_key'));
        $this->assertSame('fallback', request_string('zztest_key', 'fallback'));
        $this->assertNull(request_string('zztest_key', null));
    }

    public function testRequestStringFallsBackToDefaultWhenSubmittedAsAnArray(): void
    {
        $_REQUEST['zztest_key'] = ['1', '2'];

        $this->assertSame('', request_string('zztest_key'));
        $this->assertNull(request_string('zztest_key', null));
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

    public function testPostArrayPassesThroughAnArrayValue(): void
    {
        $_POST['zztest_key'] = ['a', 'b'];

        $this->assertSame(['a', 'b'], post_array('zztest_key'));
    }

    public function testPostArrayFallsBackToDefaultWhenMissing(): void
    {
        $this->assertSame([], post_array('zztest_key'));
        $this->assertSame(['x'], post_array('zztest_key', ['x']));
    }

    public function testPostArrayFallsBackToDefaultWhenSubmittedAsAScalar(): void
    {
        // The mirror-image bug: a field that's supposed to be array-shaped
        // (name="links[]") submitted as a plain scalar instead (links=foo)
        // would otherwise reach count()/array access downstream, which is
        // just as fatal under PHP 8 as the reverse case post_string() guards
        // against.
        $_POST['zztest_key'] = 'not an array';

        $this->assertSame([], post_array('zztest_key'));
    }

    public function testIsValidTimeOfDayAcceptsHoursAndMinutes(): void
    {
        $this->assertTrue(is_valid_time_of_day('09:00'));
        $this->assertTrue(is_valid_time_of_day('23:59'));
        $this->assertTrue(is_valid_time_of_day('00:00'));
    }

    public function testIsValidTimeOfDayAcceptsHoursMinutesAndSeconds(): void
    {
        $this->assertTrue(is_valid_time_of_day('09:00:30'));
    }

    public function testIsValidTimeOfDayRejectsOutOfRangeOrMalformedValues(): void
    {
        $this->assertFalse(is_valid_time_of_day('24:00'));
        $this->assertFalse(is_valid_time_of_day('09:60'));
        $this->assertFalse(is_valid_time_of_day('9:00'));
        $this->assertFalse(is_valid_time_of_day('not a time'));
        $this->assertFalse(is_valid_time_of_day(''));
    }

    public function testTimeOfDayToSecondsParsesHoursMinutesSeconds(): void
    {
        $this->assertSame(9 * 3600 + 30 * 60 + 15, time_of_day_to_seconds('09:30:15'));
    }

    public function testTimeOfDayToSecondsParsesHoursMinutesOnly(): void
    {
        $this->assertSame(9 * 3600 + 30 * 60, time_of_day_to_seconds('09:30'));
    }

    public function testDayStartTimestampReturnsMidnightOfTheSameDay(): void
    {
        $midday = mktime(14, 30, 0, 3, 15, 2026);

        $this->assertSame(mktime(0, 0, 0, 3, 15, 2026), day_start_timestamp($midday));
    }

    public function testComputeDayExceptionsReturnsNothingWhenNotScheduled(): void
    {
        $date = mktime(0, 0, 0, 1, 6, 2026);

        $this->assertSame([], compute_day_exceptions($date, null, [], 10));
    }

    public function testComputeDayExceptionsFlagsAbsenceWhenScheduledButNoInPunch(): void
    {
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];

        $this->assertSame(
            [['type' => 'absence']],
            compute_day_exceptions($date, $schedule, [], 10)
        );
    }

    public function testComputeDayExceptionsReturnsNothingWhenOnTime(): void
    {
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];
        $punches = [
            ['in_or_out' => 1, 'timestamp' => mktime(9, 0, 0, 1, 6, 2026)],
            ['in_or_out' => 0, 'timestamp' => mktime(17, 0, 0, 1, 6, 2026)],
        ];

        $this->assertSame([], compute_day_exceptions($date, $schedule, $punches, 10));
    }

    public function testComputeDayExceptionsFlagsLateArrivalBeyondGrace(): void
    {
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];
        // 25 minutes late, past the 10-minute grace period.
        $punches = [['in_or_out' => 1, 'timestamp' => mktime(9, 25, 0, 1, 6, 2026)]];

        $this->assertSame(
            [['type' => 'late', 'minutes' => 25]],
            compute_day_exceptions($date, $schedule, $punches, 10)
        );
    }

    public function testComputeDayExceptionsDoesNotFlagLateWithinGrace(): void
    {
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];
        $punches = [['in_or_out' => 1, 'timestamp' => mktime(9, 5, 0, 1, 6, 2026)]];

        $this->assertSame([], compute_day_exceptions($date, $schedule, $punches, 10));
    }

    public function testComputeDayExceptionsFlagsEarlyDeparture(): void
    {
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];
        $punches = [
            ['in_or_out' => 1, 'timestamp' => mktime(9, 0, 0, 1, 6, 2026)],
            // 40 minutes early, past the 10-minute grace period.
            ['in_or_out' => 0, 'timestamp' => mktime(16, 20, 0, 1, 6, 2026)],
        ];

        $this->assertSame(
            [['type' => 'early', 'minutes' => 40]],
            compute_day_exceptions($date, $schedule, $punches, 10)
        );
    }

    public function testComputeDayExceptionsDoesNotFlagEarlyWhenStillClockedIn(): void
    {
        // No "out" punch at all yet (still on shift) -- shouldn't be treated
        // as having left early just because there's no departure recorded.
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];
        $punches = [['in_or_out' => 1, 'timestamp' => mktime(9, 0, 0, 1, 6, 2026)]];

        $this->assertSame([], compute_day_exceptions($date, $schedule, $punches, 10));
    }

    public function testComputeDayExceptionsDoesNotFlagEarlyForALunchBreak(): void
    {
        // An earlier "out" (lunch break) followed by a later "in" must not
        // be mistaken for the day's departure -- only the day's truly last
        // punch (still "in" here, since they haven't clocked out yet for
        // the day) determines whether they left early.
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];
        $punches = [
            ['in_or_out' => 1, 'timestamp' => mktime(9, 0, 0, 1, 6, 2026)],
            ['in_or_out' => 0, 'timestamp' => mktime(12, 0, 0, 1, 6, 2026)], // lunch out
            ['in_or_out' => 1, 'timestamp' => mktime(13, 0, 0, 1, 6, 2026)], // lunch in
        ];

        $this->assertSame([], compute_day_exceptions($date, $schedule, $punches, 10));
    }

    public function testComputeDayExceptionsFlagsEarlyDepartureAfterALunchBreak(): void
    {
        // Same lunch break, but this time followed by a genuine early
        // departure -- the lunch "out" at noon must not be the one compared
        // against the scheduled end.
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:00:00', 'end_time' => '17:00:00'];
        $punches = [
            ['in_or_out' => 1, 'timestamp' => mktime(9, 0, 0, 1, 6, 2026)],
            ['in_or_out' => 0, 'timestamp' => mktime(12, 0, 0, 1, 6, 2026)], // lunch out
            ['in_or_out' => 1, 'timestamp' => mktime(13, 0, 0, 1, 6, 2026)], // lunch in
            ['in_or_out' => 0, 'timestamp' => mktime(16, 20, 0, 1, 6, 2026)], // 40 min early
        ];

        $this->assertSame(
            [['type' => 'early', 'minutes' => 40]],
            compute_day_exceptions($date, $schedule, $punches, 10)
        );
    }

    public function testComputeDayExceptionsGraceBoundaryIsExactNotOverBy1Second(): void
    {
        // Exactly at the grace boundary (10:00 sharp with a 10-minute grace
        // starting at 09:00) should not be flagged -- only strictly beyond it.
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '09:50:00', 'end_time' => '17:00:00'];
        $punches = [['in_or_out' => 1, 'timestamp' => mktime(10, 0, 0, 1, 6, 2026)]];

        $this->assertSame([], compute_day_exceptions($date, $schedule, $punches, 10));
    }

    public function testComputeDayExceptionsHandlesOvernightShiftLateCheck(): void
    {
        // An overnight shift (22:00-06:00): "late" is checked against the
        // scheduled start on this same calendar date, unaffected by the
        // shift crossing midnight.
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '22:00:00', 'end_time' => '06:00:00'];
        // 30 minutes late clocking in for the overnight shift.
        $punches = [['in_or_out' => 1, 'timestamp' => mktime(22, 30, 0, 1, 6, 2026)]];

        $this->assertSame(
            [['type' => 'late', 'minutes' => 30]],
            compute_day_exceptions($date, $schedule, $punches, 10)
        );
    }

    public function testComputeDayExceptionsHandlesOvernightShiftEarlyCheck(): void
    {
        // Leaving at 23:00 on the shift's start date, for a shift scheduled
        // until 06:00 the *next* day, is early -- confirms scheduled_end is
        // correctly rolled onto the following day rather than compared
        // against 06:00 on the same date (which would make 23:00 look late,
        // not early).
        $date = mktime(0, 0, 0, 1, 6, 2026);
        $schedule = ['start_time' => '22:00:00', 'end_time' => '06:00:00'];
        $punches = [
            ['in_or_out' => 1, 'timestamp' => mktime(22, 0, 0, 1, 6, 2026)],
            ['in_or_out' => 0, 'timestamp' => mktime(23, 0, 0, 1, 6, 2026)],
        ];

        $this->assertSame(
            [['type' => 'early', 'minutes' => 7 * 60]],
            compute_day_exceptions($date, $schedule, $punches, 10)
        );
    }

    public function testParseReportDateAmericanStyle(): void
    {
        $this->assertSame(
            mktime(0, 0, 0, 7, 9, 2026),
            parse_report_date('07/09/2026', 'amer')
        );
    }

    public function testParseReportDateEuroStyleSwapsDayAndMonth(): void
    {
        $this->assertSame(
            mktime(0, 0, 0, 9, 7, 2026),
            parse_report_date('07/09/2026', 'euro')
        );
    }

    public function testParseReportDateRejectsInvalidMonthOrDay(): void
    {
        $this->assertNull(parse_report_date('13/09/2026', 'amer'));
        $this->assertNull(parse_report_date('07/32/2026', 'amer'));
    }

    public function testParseReportDateRejectsMalformedOrEmptyInput(): void
    {
        $this->assertNull(parse_report_date('not a date', 'amer'));
        $this->assertNull(parse_report_date('', 'amer'));
    }
}
