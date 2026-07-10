<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_with_cwd(TIMECLOCK_ROOT . '/punchclock', TIMECLOCK_ROOT . '/punchclock/lib.common.php');

final class LibCommonPureTest extends TestCase
{
    public function testMakeIdAddsPrefixAndReplacesSpacesWithUnderscores(): void
    {
        $this->assertSame('emp_John_Smith', make_id('John Smith'));
    }

    public function testUnmakeIdReversesMakeId(): void
    {
        $this->assertSame('John Smith', unmake_id('emp_John_Smith'));
    }

    public function testComputeHoursBetweenTwoTimestamps(): void
    {
        $start = mktime(9, 0, 0, 1, 1, 2026);
        $end = mktime(17, 30, 0, 1, 1, 2026);

        $this->assertEqualsWithDelta(8.5, compute_hours($start, $end), 0.001);
    }

    public function testComputeOvertimeHoursZeroWhenUnderWeeklyLimit(): void
    {
        $GLOBALS['overtime_week_limit'] = 35;

        $this->assertSame(0, compute_overtime_hours(5, 10));
    }

    public function testComputeOvertimeHoursWhenCrossingWeeklyLimit(): void
    {
        $GLOBALS['overtime_week_limit'] = 35;

        // 30 hours already worked this week + 10 more crosses the 35-hour
        // limit by 5 hours, and the overage (5) is less than $hours (10).
        $this->assertSame(5, compute_overtime_hours(10, 30));
    }

    public function testComputeOvertimeHoursCappedAtHoursWorkedThisCall(): void
    {
        $GLOBALS['overtime_week_limit'] = 35;

        // Already 40 hours in (past the limit before this call even starts),
        // so the entire 2 hours worked in this call are overtime.
        $this->assertSame(2, compute_overtime_hours(2, 40));
    }

    public function testComputeOvertimeHoursDisabledWhenLimitIsZero(): void
    {
        $GLOBALS['overtime_week_limit'] = 0;

        $this->assertSame(0, compute_overtime_hours(10, 100));
    }

    public function testComputeDayHoursWhenEntirelyWithinOneDay(): void
    {
        $GLOBALS['one_day'] = 24 * 60 * 60;
        $start = mktime(9, 0, 0, 1, 1, 2026);
        $end = mktime(17, 0, 0, 1, 1, 2026);

        $this->assertEqualsWithDelta(8.0, compute_day_hours('20260101', $start, $end), 0.001);
    }

    public function testComputeDayHoursZeroWhenDateFallsOutsideRange(): void
    {
        $GLOBALS['one_day'] = 24 * 60 * 60;
        $start = mktime(9, 0, 0, 1, 1, 2026);
        $end = mktime(17, 0, 0, 1, 1, 2026);

        $this->assertSame(0, compute_day_hours('20260215', $start, $end));
    }

    public function testHrsMinFormatsHoursAndMinutes(): void
    {
        $this->assertSame('08:30', hrs_min(8.5));
        $this->assertSame('01:15', hrs_min(1.25));
        $this->assertSame('00:00', hrs_min(0));
    }

    public function testMakeTimestampUsesConfiguredCalendarStyle(): void
    {
        $GLOBALS['calendar_style'] = 'amer';

        $this->assertSame(mktime(0, 0, 0, 7, 9, 2026), make_timestamp('07/09/2026'));
    }

    public function testMakeTimestampEuroStyleSwapsMonthAndDay(): void
    {
        $GLOBALS['calendar_style'] = 'euro';

        $this->assertSame(mktime(0, 0, 0, 9, 7, 2026), make_timestamp('07/09/2026'));
    }

    public function testServerTimezoneOffsetZeroWhenDisabled(): void
    {
        $GLOBALS['use_server_tz'] = 'no';

        $this->assertSame(0, server_timezone_offset());
    }

    public function testServerTimezoneOffsetUsesDateZWhenEnabled(): void
    {
        $GLOBALS['use_server_tz'] = 'yes';

        $this->assertSame(date('Z'), server_timezone_offset());
    }

    public function testBoolRecognizesTruthyStrings(): void
    {
        $this->assertTrue(bool('yes'));
        $this->assertTrue(bool('true'));
        $this->assertTrue(bool('1'));
    }

    public function testBoolRecognizesFalsyStrings(): void
    {
        $this->assertFalse(bool('no'));
        $this->assertFalse(bool('false'));
        $this->assertFalse(bool('0'));
        $this->assertFalse(bool('000'));
        $this->assertFalse(bool(''));
        $this->assertFalse(bool(null));
        $this->assertFalse(bool());
    }

    public function testMsgWrapsTextInMessageDiv(): void
    {
        $this->assertSame("<div class=\"message\">\nHello\n</div>", msg('Hello'));
    }

    public function testErrorMsgWrapsTextInErrorDiv(): void
    {
        $this->assertSame("<div class=\"error\">\nOops\n</div>", error_msg('Oops'));
    }

    public function testTurnOffMagicQuotesIsANoOp(): void
    {
        $this->expectNotToPerformAssertions();
        turn_off_magic_quotes();
    }
}
