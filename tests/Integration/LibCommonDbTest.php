<?php

namespace Tests\Integration;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once TIMECLOCK_ROOT . '/functions.php';

$__cwd = chdir_for_require(TIMECLOCK_ROOT . '/punchclock');
require_once TIMECLOCK_ROOT . '/punchclock/lib.common.php';
chdir_restore($__cwd);

final class LibCommonDbTest extends DatabaseTestCase
{
    private const EMPFULLNAME = 'zztest_libcommon_employee';
    private const DISPLAYNAME = 'ZZ Test LibCommon Employee';

    protected function tearDown(): void
    {
        tc_delete('employees', 'empfullname = ?', self::EMPFULLNAME);
        $GLOBALS['use_passwd'] = 'yes';
    }

    private function insertTestEmployee(array $overrides = []): void
    {
        $row = array_merge([
            'empfullname' => self::EMPFULLNAME,
            'employee_passwd' => 'xy.RY2HT1QTc2', // legacy crypt('x', 'xy') hash
            'displayname' => self::DISPLAYNAME,
            'email' => 'zztest-libcommon@example.com',
            'groups' => 'ZZ Test Group',
            'office' => 'ZZ Test Office',
        ], $overrides);

        tc_insert_strings('employees', $row);
    }

    public function testLookupEmployeeByExactEmpfullname(): void
    {
        $this->insertTestEmployee();

        $this->assertSame(self::EMPFULLNAME, lookup_employee(self::EMPFULLNAME));
    }

    public function testLookupEmployeeByDisplaynameCaseInsensitively(): void
    {
        $this->insertTestEmployee();

        $this->assertSame(self::EMPFULLNAME, lookup_employee(strtoupper(self::DISPLAYNAME)));
    }

    public function testLookupEmployeeReturnsNullWhenNotFound(): void
    {
        $this->assertNull(lookup_employee('nobody-with-this-name'));
    }

    public function testGetEmployeeName(): void
    {
        $this->insertTestEmployee();

        $this->assertSame(self::DISPLAYNAME, get_employee_name(self::EMPFULLNAME));
    }

    public function testGetEmployeePassword(): void
    {
        $this->insertTestEmployee(['employee_passwd' => 'a-hash-value']);

        $this->assertSame('a-hash-value', get_employee_password(self::EMPFULLNAME));
    }

    public function testIsValidPasswordAcceptsCorrectModernHash(): void
    {
        $GLOBALS['use_passwd'] = 'yes';
        $this->insertTestEmployee(['employee_passwd' => password_hash('correcthorse', PASSWORD_DEFAULT)]);

        $this->assertTrue(is_valid_password(self::EMPFULLNAME, 'correcthorse'));
        $this->assertFalse(is_valid_password(self::EMPFULLNAME, 'wrong'));
    }

    public function testIsValidPasswordUpgradesALegacyHashOnSuccessfulVerification(): void
    {
        $GLOBALS['use_passwd'] = 'yes';
        $this->insertTestEmployee(['employee_passwd' => crypt('correcthorse', 'xy')]);

        $this->assertTrue(is_valid_password(self::EMPFULLNAME, 'correcthorse'));

        $newHash = get_employee_password(self::EMPFULLNAME);
        $this->assertStringStartsWith('$2y$', $newHash);
    }

    public function testSaveEmployeePasswordHashesAndPersists(): void
    {
        $this->insertTestEmployee();

        $result = save_employee_password(self::EMPFULLNAME, 'a-new-password');

        $this->assertTrue($result);
        $storedHash = get_employee_password(self::EMPFULLNAME);
        $this->assertTrue(password_verify('a-new-password', $storedHash));
    }

    public function testGetEmployeeStatusReturnsInOutColorAndNotes(): void
    {
        $this->insertTestEmployee();
        tc_insert_strings('punchlist', [
            'punchitems' => 'ZZTestStatus',
            'color' => '#123456',
            'in_or_out' => '1',
        ]);
        $timestamp = 1234567890;
        tc_update_strings('employees', ['tstamp' => $timestamp], 'empfullname = ?', self::EMPFULLNAME);
        tc_insert_strings('info', [
            'fullname' => self::EMPFULLNAME,
            'inout' => 'ZZTestStatus',
            'timestamp' => $timestamp,
            'notes' => 'a note',
        ]);

        [$inOrOut, $color, $inout, $rowTimestamp, $notes] = get_employee_status(self::EMPFULLNAME);

        $this->assertEquals(1, $inOrOut);
        $this->assertSame('#123456', $color);
        $this->assertSame('ZZTestStatus', $inout);
        $this->assertEquals($timestamp, $rowTimestamp);
        $this->assertSame('a note', $notes);

        tc_delete('info', 'fullname = ?', self::EMPFULLNAME);
        tc_delete('punchlist', 'punchitems = ?', 'ZZTestStatus');
    }
}
