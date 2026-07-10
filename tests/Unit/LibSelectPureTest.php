<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once TIMECLOCK_ROOT . '/punchclock/lib.select.php';

final class LibSelectPureTest extends TestCase
{
    public function testMakeLookupArrayFromNull(): void
    {
        $this->assertSame([], make_lookup_array(null));
    }

    public function testMakeLookupArrayFromScalar(): void
    {
        $this->assertSame(['val2' => 0], make_lookup_array('val2'));
    }

    public function testMakeLookupArrayFromArray(): void
    {
        $this->assertSame(['val1' => 0, 'val2' => 1], make_lookup_array(['val1', 'val2']));
    }

    public function testSelectOptionsArrRendersOneDimensionalArray(): void
    {
        $lookup = make_lookup_array('val2');

        $html = _select_options_arr(['val1', 'val2', 'val3'], $lookup);

        $this->assertSame(
            "<option value=\"val1\">val1</option>\n"
            . "<option value=\"val2\" selected=\"selected\">val2</option>\n"
            . "<option value=\"val3\">val3</option>",
            $html
        );
    }

    public function testSelectOptionsArrEscapesValues(): void
    {
        $lookup = make_lookup_array(null);

        $html = _select_options_arr(['<script>'], $lookup);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString(htmlentities('<script>'), $html);
    }

    public function testSelectOptionsArr2RendersValueTextPairs(): void
    {
        $lookup = make_lookup_array('val1');

        $html = _select_options_arr2([['val1', 'text1'], ['val2', 'text2']], $lookup);

        $this->assertSame(
            "<option value=\"val1\" selected=\"selected\">text1</option>\n"
            . "<option value=\"val2\">text2</option>",
            $html
        );
    }

    public function testSelectOptionsDelegatesToArrHelperForArrayInput(): void
    {
        $html = select_options(['val1', 'val2'], 'val2');

        $this->assertSame(
            "<option value=\"val1\">val1</option>\n"
            . "<option value=\"val2\" selected=\"selected\">val2</option>",
            $html
        );
    }

    public function testSelectOptionsDelegatesToArr2HelperForArrayOfPairs(): void
    {
        $html = select_options([['a', 'Apple'], ['b', 'Banana']], 'b');

        $this->assertSame(
            "<option value=\"a\">Apple</option>\n"
            . "<option value=\"b\" selected=\"selected\">Banana</option>",
            $html
        );
    }
}
