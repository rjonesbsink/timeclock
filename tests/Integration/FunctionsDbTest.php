<?php

namespace Tests\Integration;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once TIMECLOCK_ROOT . '/functions.php';

final class FunctionsDbTest extends DatabaseTestCase
{
    private const EMPFULLNAME = 'zztest_functions_employee';
    private const EMPFULLNAME_SHIFT = 'zztest_openshift_employee';

    protected function tearDown(): void
    {
        tc_delete('employees', 'empfullname = ?', self::EMPFULLNAME);
        tc_delete('info', 'fullname = ?', self::EMPFULLNAME_SHIFT);
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
}
