<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

$__cwd = chdir_for_require(TIMECLOCK_ROOT . '/punchclock');
require_once TIMECLOCK_ROOT . '/punchclock/lib.common.php';
chdir_restore($__cwd);

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

    public function testComputeOvertimeHoursZeroWhenUnderDailyLimit(): void
    {
        $GLOBALS['overtime_week_limit'] = 0;
        $GLOBALS['overtime_daily_limit'] = 8;

        // 3 hours already worked today + 5 more = 8, not over the limit.
        $this->assertSame(0, compute_overtime_hours(5, 0, 3));
    }

    public function testComputeOvertimeHoursWhenCrossingDailyLimit(): void
    {
        $GLOBALS['overtime_week_limit'] = 0;
        $GLOBALS['overtime_daily_limit'] = 8;

        // 6 hours already worked today + 5 more crosses the 8-hour daily
        // limit by 3, and the overage (3) is less than $hours (5).
        $this->assertSame(3, compute_overtime_hours(5, 0, 6));
    }

    public function testComputeOvertimeHoursIgnoresDailyLimitWhenDayHoursNotGiven(): void
    {
        // Backward compatibility: existing 2-argument callers must be
        // unaffected by a configured daily limit if they don't pass $day_hours.
        $GLOBALS['overtime_week_limit'] = 0;
        $GLOBALS['overtime_daily_limit'] = 8;

        $this->assertSame(0, compute_overtime_hours(10, 0));
    }

    public function testComputeOvertimeHoursUsesDailyWhenItExceedsWeekly(): void
    {
        $GLOBALS['overtime_week_limit'] = 40;
        $GLOBALS['overtime_daily_limit'] = 8;

        // Weekly: 10 + 10 = 20, nowhere near the 40-hour limit -> 0 weekly overtime.
        // Daily: 6 + 10 = 16, crosses the 8-hour limit by 8, capped at $hours (10) -> 8.
        $this->assertSame(8, compute_overtime_hours(10, 10, 6));
    }

    public function testComputeOvertimeHoursUsesWeeklyWhenItExceedsDaily(): void
    {
        $GLOBALS['overtime_week_limit'] = 40;
        $GLOBALS['overtime_daily_limit'] = 8;

        // Weekly: 38 + 10 = 48, crosses the 40-hour limit by 8 -> 8 weekly overtime.
        // Daily: 0 + 10 = 10, crosses the 8-hour limit by 2 -> 2 daily overtime.
        $this->assertSame(8, compute_overtime_hours(10, 38, 0));
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

    public function testComputeWorkHoursSplitsAnOvernightSegmentAcrossCalendarDays(): void
    {
        $GLOBALS['one_day'] = 24 * 60 * 60;
        $GLOBALS['overtime_week_limit'] = 0;
        $GLOBALS['overtime_daily_limit'] = 0;

        $start = mktime(22, 0, 0, 1, 6, 2020);
        $end = mktime(7, 30, 0, 1, 7, 2020);

        [$hours, $overtime, $dayHoursByDate] = compute_work_hours($start, $end, 0);

        $this->assertEqualsWithDelta(9.5, $hours, 0.001);
        $this->assertSame(0, $overtime);
        $this->assertEqualsWithDelta(2.0, $dayHoursByDate['20200106'], 0.001);
        $this->assertEqualsWithDelta(7.5, $dayHoursByDate['20200107'], 0.001);
    }

    public function testComputeWorkHoursAppliesDailyOvertimePerDayWithinAnOvernightSegment(): void
    {
        $GLOBALS['one_day'] = 24 * 60 * 60;
        $GLOBALS['overtime_week_limit'] = 0;
        $GLOBALS['overtime_daily_limit'] = 1;

        $start = mktime(22, 0, 0, 1, 6, 2020);
        $end = mktime(7, 30, 0, 1, 7, 2020);

        [$hours, $overtime, $dayHoursByDate] = compute_work_hours($start, $end, 0);

        // Day 1: 2 hours, 1 over the 1-hour daily limit.
        // Day 2: 7.5 hours, all but the first hour is over the limit (6.5).
        $this->assertEqualsWithDelta(7.5, $overtime, 0.001);
        $this->assertEqualsWithDelta(2.0, $hours, 0.001);
        $this->assertEqualsWithDelta(2.0, $dayHoursByDate['20200106'], 0.001);
        $this->assertEqualsWithDelta(7.5, $dayHoursByDate['20200107'], 0.001);
    }

    public function testComputeWorkHoursCarriesRunningDayTotalsAcrossSeparateCalls(): void
    {
        // Simulates two shifts on the same day (as Timecard::walk() would
        // process across two separate punch-in/punch-out segments), where
        // only the combined total crosses the daily limit.
        $GLOBALS['one_day'] = 24 * 60 * 60;
        $GLOBALS['overtime_week_limit'] = 0;
        $GLOBALS['overtime_daily_limit'] = 6;

        $morningStart = mktime(9, 0, 0, 1, 6, 2020);
        $morningEnd = mktime(13, 0, 0, 1, 6, 2020);
        [$hours1, $overtime1, $dayHoursByDate] = compute_work_hours($morningStart, $morningEnd, 0);

        $this->assertEqualsWithDelta(4.0, $hours1, 0.001);
        $this->assertSame(0, $overtime1);

        $afternoonStart = mktime(14, 0, 0, 1, 6, 2020);
        $afternoonEnd = mktime(18, 0, 0, 1, 6, 2020);
        [$hours2, $overtime2, $dayHoursByDate] = compute_work_hours($afternoonStart, $afternoonEnd, 4, $dayHoursByDate);

        // 4 hours already today + 4 more = 8, crosses the 6-hour limit by 2.
        $this->assertEqualsWithDelta(2.0, $hours2, 0.001);
        $this->assertEqualsWithDelta(2.0, $overtime2, 0.001);
        $this->assertEqualsWithDelta(8.0, $dayHoursByDate['20200106'], 0.001);
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
