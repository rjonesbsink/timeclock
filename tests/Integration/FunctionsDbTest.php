<?php

namespace Tests\Integration;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once TIMECLOCK_ROOT . '/functions.php';

final class FunctionsDbTest extends DatabaseTestCase
{
    private const EMPFULLNAME = 'zztest_functions_employee';
    private const EMPFULLNAME_SHIFT = 'zztest_openshift_employee';
    private const EMPFULLNAME_SCHEDULE = 'zztest_schedule_employee';
    private const EMPFULLNAME_EXCEPTIONS = 'zztest_exceptions_employee';

    protected function tearDown(): void
    {
        tc_delete('employees', 'empfullname = ?', self::EMPFULLNAME);
        tc_delete('info', 'fullname = ?', self::EMPFULLNAME_SHIFT);
        tc_delete('schedules', 'empfullname = ?', self::EMPFULLNAME_SCHEDULE);
        tc_delete('schedules', 'empfullname = ?', self::EMPFULLNAME_EXCEPTIONS);
        tc_delete('info', 'fullname = ?', self::EMPFULLNAME_EXCEPTIONS);
    }

    private function insertExceptionPunch(string $inout, int $timestamp): void
    {
        tc_insert_strings('info', [
            'fullname' => self::EMPFULLNAME_EXCEPTIONS,
            'inout' => $inout,
            'timestamp' => $timestamp,
            'notes' => '',
        ]);
    }

    private function insertPunch(string $inout, int $timestamp): void
    {
        tc_insert_strings('info', [
            'fullname' => self::EMPFULLNAME_SHIFT,
            'inout' => $inout,
            'timestamp' => $timestamp,
            'notes' => '',
        ]);
    }

    private function insertTestEmployee(array $overrides = []): int
    {
        $row = array_merge([
            'empfullname' => self::EMPFULLNAME,
            'employee_passwd' => 'xy.RY2HT1QTc2', // legacy crypt('x', 'xy') hash
            'displayname' => 'ZZ Test Employee',
            'email' => 'zztest@example.com',
            'groups' => 'ZZ Test Group',
            'office' => 'ZZ Test Office',
        ], $overrides);

        return tc_insert_strings('employees', $row);
    }

    public function testTcInsertStringsThenTcSelectRoundTrip(): void
    {
        $this->insertTestEmployee();

        $result = tc_select('displayname, email', 'employees', 'empfullname = ?', self::EMPFULLNAME);

        $row = mysqli_fetch_assoc($result);
        $this->assertSame('ZZ Test Employee', $row['displayname']);
        $this->assertSame('zztest@example.com', $row['email']);
    }

    public function testTcSelectValueReturnsSingleScalar(): void
    {
        $this->insertTestEmployee();

        $displayname = tc_select_value('displayname', 'employees', 'empfullname = ?', self::EMPFULLNAME);

        $this->assertSame('ZZ Test Employee', $displayname);
    }

    public function testTcSelectValueReturnsNullWhenNoMatch(): void
    {
        $value = tc_select_value('displayname', 'employees', 'empfullname = ?', 'no-such-employee');

        $this->assertNull($value);
    }

    public function testTcUpdateStringsModifiesExistingRow(): void
    {
        $this->insertTestEmployee();

        tc_update_strings(
            'employees',
            ['displayname' => 'Updated Name'],
            'empfullname = ?',
            self::EMPFULLNAME
        );

        $this->assertSame(
            'Updated Name',
            tc_select_value('displayname', 'employees', 'empfullname = ?', self::EMPFULLNAME)
        );
    }

    public function testTcDeleteRemovesTheRow(): void
    {
        $this->insertTestEmployee();

        tc_delete('employees', 'empfullname = ?', self::EMPFULLNAME);

        $this->assertNull(
            tc_select_value('displayname', 'employees', 'empfullname = ?', self::EMPFULLNAME)
        );
    }

    public function testTcMaybeUpgradePasswordRewritesALegacyHashAfterVerification(): void
    {
        $this->insertTestEmployee(['employee_passwd' => crypt('correcthorse', 'xy')]);

        tc_maybe_upgrade_password(self::EMPFULLNAME, 'correcthorse', crypt('correcthorse', 'xy'));

        $newHash = tc_select_value('employee_passwd', 'employees', 'empfullname = ?', self::EMPFULLNAME);
        $this->assertStringStartsWith('$2y$', $newHash);
        $this->assertTrue(password_verify('correcthorse', $newHash));
    }

    public function testTcMaybeUpgradePasswordLeavesAModernHashAlone(): void
    {
        $modernHash = password_hash('correcthorse', PASSWORD_DEFAULT);
        $this->insertTestEmployee(['employee_passwd' => $modernHash]);

        tc_maybe_upgrade_password(self::EMPFULLNAME, 'correcthorse', $modernHash);

        $this->assertSame(
            $modernHash,
            tc_select_value('employee_passwd', 'employees', 'empfullname = ?', self::EMPFULLNAME)
        );
    }

    public function testHtmlOptionsBuildsOptionTagsFromAResultSet(): void
    {
        $this->insertTestEmployee();

        $result = tc_select('empfullname, displayname', 'employees', 'empfullname = ?', self::EMPFULLNAME);

        $html = html_options($result, self::EMPFULLNAME);

        $this->assertSame(
            '<option value="' . self::EMPFULLNAME . '" selected>ZZ Test Employee</option>' . "\n",
            $html
        );
    }

    public function testHadOpenShiftBeforeReturnsFalseWithNoPriorPunches(): void
    {
        $this->assertFalse(
            had_open_shift_before(self::EMPFULLNAME_SHIFT, mktime(0, 0, 0, 1, 6, 2020))
        );
    }

    public function testHadOpenShiftBeforeReturnsTrueWhenMostRecentPriorPunchIsIn(): void
    {
        $this->insertPunch('in', mktime(22, 0, 0, 1, 5, 2020));

        $this->assertTrue(
            had_open_shift_before(self::EMPFULLNAME_SHIFT, mktime(0, 0, 0, 1, 6, 2020))
        );
    }

    public function testHadOpenShiftBeforeReturnsFalseWhenMostRecentPriorPunchIsOut(): void
    {
        $this->insertPunch('in', mktime(9, 0, 0, 1, 5, 2020));
        $this->insertPunch('out', mktime(17, 0, 0, 1, 5, 2020));

        $this->assertFalse(
            had_open_shift_before(self::EMPFULLNAME_SHIFT, mktime(0, 0, 0, 1, 6, 2020))
        );
    }

    public function testHadOpenShiftBeforeIgnoresPunchesAtOrAfterTheBoundary(): void
    {
        // An "in" punch exactly at (or after) the boundary doesn't count --
        // only a punch strictly before it can represent a shift already
        // open going into that moment.
        $this->insertPunch('in', mktime(0, 0, 0, 1, 6, 2020));

        $this->assertFalse(
            had_open_shift_before(self::EMPFULLNAME_SHIFT, mktime(0, 0, 0, 1, 6, 2020))
        );
    }

    public function testHadOpenShiftBeforeUsesTheMostRecentPunchNotTheOldest(): void
    {
        // Oldest punch is "in", but the most recent one before the boundary
        // is "out" -- the shift was already closed, so this must be false
        // even though an "in" punch exists somewhere earlier in history.
        $this->insertPunch('in', mktime(9, 0, 0, 1, 4, 2020));
        $this->insertPunch('out', mktime(17, 0, 0, 1, 4, 2020));
        $this->insertPunch('in', mktime(9, 0, 0, 1, 5, 2020));
        $this->insertPunch('out', mktime(17, 0, 0, 1, 5, 2020));

        $this->assertFalse(
            had_open_shift_before(self::EMPFULLNAME_SHIFT, mktime(0, 0, 0, 1, 6, 2020))
        );
    }

    public function testGetEmployeeScheduleReturnsEmptyArrayWithNoSchedule(): void
    {
        $this->assertSame([], get_employee_schedule(self::EMPFULLNAME_SCHEDULE));
    }

    public function testSetEmployeeScheduleThenGetEmployeeScheduleRoundTrips(): void
    {
        set_employee_schedule(self::EMPFULLNAME_SCHEDULE, [
            1 => ['start_time' => '09:00', 'end_time' => '17:00'],
            3 => ['start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        $schedule = get_employee_schedule(self::EMPFULLNAME_SCHEDULE);

        $this->assertCount(2, $schedule);
        $this->assertSame('09:00:00', $schedule[1]['start_time']);
        $this->assertSame('17:00:00', $schedule[1]['end_time']);
        $this->assertSame('09:00:00', $schedule[3]['start_time']);
        $this->assertArrayNotHasKey(0, $schedule);
    }

    public function testSetEmployeeScheduleReplacesThePreviousSchedule(): void
    {
        set_employee_schedule(self::EMPFULLNAME_SCHEDULE, [
            1 => ['start_time' => '09:00', 'end_time' => '17:00'],
            2 => ['start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        // Re-saving with only day 1 must remove day 2's row, not just add to it.
        set_employee_schedule(self::EMPFULLNAME_SCHEDULE, [
            1 => ['start_time' => '10:00', 'end_time' => '18:00'],
        ]);

        $schedule = get_employee_schedule(self::EMPFULLNAME_SCHEDULE);

        $this->assertCount(1, $schedule);
        $this->assertSame('10:00:00', $schedule[1]['start_time']);
        $this->assertArrayNotHasKey(2, $schedule);
    }

    public function testSetEmployeeScheduleSupportsAnOvernightShift(): void
    {
        // end_time earlier than start_time means the shift crosses midnight
        // (see get_employee_schedule()'s docblock) -- this must be stored
        // as-is, not rejected or silently reordered.
        set_employee_schedule(self::EMPFULLNAME_SCHEDULE, [
            5 => ['start_time' => '22:00', 'end_time' => '06:00'],
        ]);

        $schedule = get_employee_schedule(self::EMPFULLNAME_SCHEDULE);

        $this->assertSame('22:00:00', $schedule[5]['start_time']);
        $this->assertSame('06:00:00', $schedule[5]['end_time']);
    }

    public function testGetEmployeeExceptionsFlagsAbsenceForScheduledDayWithNoPunches(): void
    {
        set_employee_schedule(self::EMPFULLNAME_EXCEPTIONS, [
            1 => ['start_time' => '09:00', 'end_time' => '17:00'], // Monday
        ]);

        $from = mktime(0, 0, 0, 1, 6, 2020); // Monday
        $to = mktime(0, 0, 0, 1, 7, 2020); // exclusive upper bound

        $exceptions = get_employee_exceptions(self::EMPFULLNAME_EXCEPTIONS, $from, $to, 0, 10);

        $this->assertSame(
            [['type' => 'absence', 'date' => '20200106', 'empfullname' => self::EMPFULLNAME_EXCEPTIONS]],
            $exceptions
        );
    }

    public function testGetEmployeeExceptionsFlagsLateArrival(): void
    {
        set_employee_schedule(self::EMPFULLNAME_EXCEPTIONS, [
            1 => ['start_time' => '09:00', 'end_time' => '17:00'],
        ]);
        $this->insertExceptionPunch('in', mktime(9, 25, 0, 1, 6, 2020));
        $this->insertExceptionPunch('out', mktime(17, 0, 0, 1, 6, 2020));

        $from = mktime(0, 0, 0, 1, 6, 2020);
        $to = mktime(0, 0, 0, 1, 7, 2020);

        $exceptions = get_employee_exceptions(self::EMPFULLNAME_EXCEPTIONS, $from, $to, 0, 10);

        $this->assertSame(
            [['type' => 'late', 'minutes' => 25, 'date' => '20200106', 'empfullname' => self::EMPFULLNAME_EXCEPTIONS]],
            $exceptions
        );
    }

    public function testGetEmployeeExceptionsReturnsNothingWhenOnTime(): void
    {
        set_employee_schedule(self::EMPFULLNAME_EXCEPTIONS, [
            1 => ['start_time' => '09:00', 'end_time' => '17:00'],
        ]);
        $this->insertExceptionPunch('in', mktime(9, 0, 0, 1, 6, 2020));
        $this->insertExceptionPunch('out', mktime(17, 0, 0, 1, 6, 2020));

        $from = mktime(0, 0, 0, 1, 6, 2020);
        $to = mktime(0, 0, 0, 1, 7, 2020);

        $this->assertSame([], get_employee_exceptions(self::EMPFULLNAME_EXCEPTIONS, $from, $to, 0, 10));
    }

    public function testGetEmployeeExceptionsIgnoresDaysWithNoSchedule(): void
    {
        // No schedule at all set for this employee -- absence of punches on
        // any day must not generate exceptions (unscheduled work/no-shows
        // aren't flagged, per the chosen design).
        $from = mktime(0, 0, 0, 1, 6, 2020);
        $to = mktime(0, 0, 0, 1, 7, 2020);

        $this->assertSame([], get_employee_exceptions(self::EMPFULLNAME_EXCEPTIONS, $from, $to, 0, 10));
    }

    public function testGetEmployeeExceptionsAppliesTimezoneOffsetWhenBucketingPunchesByDate(): void
    {
        // Stored (naive) at 23:00 on Jan 5 (Sunday) -- but a +3 hour tzo
        // shifts this to 02:00 local on Jan 6 (Monday), which is the date
        // the schedule lookup and "late" check must use. If the offset
        // weren't applied, this punch would bucket to Sunday (unscheduled,
        // no exceptions at all) instead of correctly flagging it late.
        set_employee_schedule(self::EMPFULLNAME_EXCEPTIONS, [
            1 => ['start_time' => '01:00', 'end_time' => '09:00'], // Monday
        ]);
        $this->insertExceptionPunch('in', mktime(23, 0, 0, 1, 5, 2020));

        $tzo = 3 * 3600;
        $from = mktime(0, 0, 0, 1, 5, 2020);
        $to = mktime(0, 0, 0, 1, 7, 2020);

        $exceptions = get_employee_exceptions(self::EMPFULLNAME_EXCEPTIONS, $from, $to, $tzo, 10);

        $this->assertSame(
            [['type' => 'late', 'minutes' => 60, 'date' => '20200106', 'empfullname' => self::EMPFULLNAME_EXCEPTIONS]],
            $exceptions
        );
    }

    public function testGetEmployeeExceptionsAttributesAnOvernightShiftsClosingPunchToTheCorrectDay(): void
    {
        // Monday is an overnight shift (22:00-06:00); Tuesday is a normal
        // 9-5 shift. The punch that closes out Monday's shift physically
        // happens after midnight, on the Tuesday calendar date -- it must
        // be attributed to Monday's early-departure check, not bleed into
        // (or get confused with) Tuesday's own on-time 9-5 shift.
        set_employee_schedule(self::EMPFULLNAME_EXCEPTIONS, [
            1 => ['start_time' => '22:00', 'end_time' => '06:00'], // Monday, overnight
            2 => ['start_time' => '09:00', 'end_time' => '17:00'], // Tuesday, normal
        ]);
        $this->insertExceptionPunch('in', mktime(22, 0, 0, 1, 6, 2020)); // Monday 22:00, on time
        $this->insertExceptionPunch('out', mktime(5, 30, 0, 1, 7, 2020)); // Tuesday 05:30 -- 30 min early for Monday's shift
        $this->insertExceptionPunch('in', mktime(9, 0, 0, 1, 7, 2020)); // Tuesday 09:00, on time
        $this->insertExceptionPunch('out', mktime(17, 0, 0, 1, 7, 2020)); // Tuesday 17:00, on time

        $from = mktime(0, 0, 0, 1, 6, 2020);
        $to = mktime(0, 0, 0, 1, 8, 2020);

        $exceptions = get_employee_exceptions(self::EMPFULLNAME_EXCEPTIONS, $from, $to, 0, 10);

        $this->assertSame(
            [['type' => 'early', 'minutes' => 30, 'date' => '20200106', 'empfullname' => self::EMPFULLNAME_EXCEPTIONS]],
            $exceptions
        );
    }
}
