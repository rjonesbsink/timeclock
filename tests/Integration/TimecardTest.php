<?php

namespace Tests\Integration;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once TIMECLOCK_ROOT . '/functions.php';

$__cwd = chdir_for_require(TIMECLOCK_ROOT . '/punchclock');
require_once TIMECLOCK_ROOT . '/punchclock/class.Timecard.php';
chdir_restore($__cwd);

final class TimecardTest extends DatabaseTestCase
{
    private const EMPFULLNAME = 'zztest_timecard_employee';

    protected function setUp(): void
    {
        // Timecard::query()/queryPrevRecord() and walk() read these via
        // `global`, which always resolves against the true $GLOBALS
        // superglobal -- config.inc.php's cascade of plain top-level
        // assignments doesn't reliably reach it once required from inside
        // PHPUnit's own test-loading call stack, so every global these
        // methods touch is set explicitly here rather than relied upon.
        $GLOBALS['db_prefix'] = '';
        $GLOBALS['default_in_or_out'] = 1;
        $GLOBALS['overtime_week_limit'] = 35;
        $GLOBALS['show_display_name'] = 'no';
        $GLOBALS['timefmt'] = 'g:i a';
        $GLOBALS['datefmt'] = 'm/d/Y';
        $GLOBALS['timecard_list_punch_outs'] = 'yes';
        $GLOBALS['timecard_punchitem'] = 'out';
        unset($_COOKIE['tzoffset']);
    }

    protected function tearDown(): void
    {
        tc_delete('info', 'fullname = ?', self::EMPFULLNAME);
        tc_delete('employees', 'empfullname = ?', self::EMPFULLNAME);
        unset($_COOKIE['tzoffset']);
    }

    private function insertPunch(string $inout, int $timestamp, string $notes = ''): void
    {
        tc_insert_strings('info', [
            'fullname' => self::EMPFULLNAME,
            'inout' => $inout,
            'timestamp' => $timestamp,
            'notes' => $notes,
        ]);
    }

    public function testTallyOverASingleEightHourShiftInThePast(): void
    {
        // A fixed week safely in the past, so none of walk()'s "is this the
        // current week / still punched in as of now" branches can trigger --
        // and with $_COOKIE['tzoffset'] unset (see setUp), timezone_offset()
        // is always 0, so local and UTM timestamps match.
        $begin = mktime(0, 0, 0, 1, 6, 2020);
        $end = mktime(23, 59, 59, 1, 12, 2020);

        $this->insertPunch('in', mktime(9, 0, 0, 1, 6, 2020));
        $this->insertPunch('out', mktime(17, 0, 0, 1, 6, 2020));

        $timecard = new \Timecard(self::EMPFULLNAME, $begin, $end);
        [$rowCount, $totalHours, $overtimeHours, $todayHours] = $timecard->tally();

        $this->assertSame(2, $rowCount);
        $this->assertEqualsWithDelta(8.0, $totalHours, 0.001);
        $this->assertSame(0, $overtimeHours);
        $this->assertNull($todayHours, 'today_hours should stay null for a week that is not the current week');
    }

    public function testTallyAccumulatesOvertimePastTheWeeklyLimit(): void
    {
        $begin = mktime(0, 0, 0, 1, 6, 2020);
        $end = mktime(23, 59, 59, 1, 12, 2020);

        // Five 8-hour days (Mon-Fri) = 40 hours, 5 over the 35-hour limit.
        for ($day = 6; $day <= 10; $day++) {
            $this->insertPunch('in', mktime(9, 0, 0, 1, $day, 2020));
            $this->insertPunch('out', mktime(17, 0, 0, 1, $day, 2020));
        }

        $timecard = new \Timecard(self::EMPFULLNAME, $begin, $end);
        [$rowCount, $totalHours, $overtimeHours] = $timecard->tally();

        $this->assertSame(10, $rowCount);
        $this->assertEqualsWithDelta(40.0, $totalHours, 0.001);
        $this->assertEqualsWithDelta(5.0, $overtimeHours, 0.001);
    }

    public function testTallyWithNoPunchesReturnsZeroedResult(): void
    {
        $begin = mktime(0, 0, 0, 1, 6, 2020);
        $end = mktime(23, 59, 59, 1, 12, 2020);

        $timecard = new \Timecard(self::EMPFULLNAME, $begin, $end);
        [$rowCount, $totalHours, $overtimeHours, $todayHours] = $timecard->tally();

        $this->assertSame(0, $rowCount);
        $this->assertNull($totalHours, 'total_hours is only ever set once a row is processed');
        $this->assertSame(0, $overtimeHours, 'overtime_hours is unconditionally initialized to 0 in walk()');
        $this->assertNull($todayHours);
    }
}
