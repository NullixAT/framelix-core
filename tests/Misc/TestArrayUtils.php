<?php

namespace Framelix\Framelix\Tests\Misc;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Utils\ArrayUtils;
use PHPUnit\Framework\TestCase;
use const SORT_ASC;
use const SORT_DESC;

final class TestArrayUtils extends TestCase
{

    public function tests(): void
    {
        $array = [
            "foo1" => DateTime::create("2016-01-01 00:00:02"), // 1
            "foo2" => "2016-01-01 00:00:01", // 0
            "foo3" => "2017-01-01 00:00:01", // 2
            "foo6" => "2019-01-01 00:00:01", // 5
            "foo7" => "2018-01-01 00:00:01", // 3
            "foo8" => DateTime::create("2018-01-02 00:00:01") // 4
        ];
        ArrayUtils::sort($array, null, [SORT_ASC]);
        $this->stringifyArray($array);
        $this->assertEqualArray($array,
            '{"foo2":"2016-01-01 00:00:01","foo1":"2016-01-01 00:00:02","foo3":"2017-01-01 00:00:01","foo7":"2018-01-01 00:00:01","foo8":"2018-01-02 00:00:01","foo6":"2019-01-01 00:00:01"}');

        ArrayUtils::sort($array, null, [SORT_DESC]);
        $this->stringifyArray($array);
        $this->assertEqualArray($array,
            '{"foo6":"2019-01-01 00:00:01","foo8":"2018-01-02 00:00:01","foo7":"2018-01-01 00:00:01","foo3":"2017-01-01 00:00:01","foo1":"2016-01-01 00:00:02","foo2":"2016-01-01 00:00:01"}');

        $array = [
            "foo1" => DateTime::create("2016-02-01 00:00:02"), // 0
            "foo6" => DateTime::create("2019-05-01 00:00:01"), // 3
            "foo2" => DateTime::create("2016-03-01 00:00:01"), // 1
            "foo7" => DateTime::create("2018-06-01 00:00:01"), // 4
            "foo8" => DateTime::create("2018-07-02 00:00:01"), // 5
            "foo3" => DateTime::create("2017-04-01 00:00:01"), // 2
        ];
        ArrayUtils::sort($array, "getSortableValue", [SORT_DESC]);
        $this->stringifyArray($array);
        $this->assertEqualArray($array,
            '{"foo1":"2016-02-01 00:00:02","foo2":"2016-03-01 00:00:01","foo3":"2017-04-01 00:00:01","foo7":"2018-06-01 00:00:01","foo8":"2018-07-02 00:00:01","foo6":"2019-05-01 00:00:01"}');


        $array = [
            "foo1" => DateTime::create("2016-02-01 00:00:02"), // 0
            "foo6" => DateTime::create("2019-05-01 00:00:01"), // 5
            "foo2" => DateTime::create("2016-03-01 00:00:01"), // 1
            "foo7" => DateTime::create("2018-06-01 00:00:01"), // 3
            "foo8" => DateTime::create("2018-07-02 00:00:01"), // 4
            "foo3" => DateTime::create("2017-04-01 00:00:01"), // 2
        ];
        ArrayUtils::sort($array, "getTimestamp", [[SORT_NUMERIC, SORT_ASC]]);
        $this->stringifyArray($array);
        $this->assertEqualArray($array,
            '{"foo1":"2016-02-01 00:00:02","foo2":"2016-03-01 00:00:01","foo3":"2017-04-01 00:00:01","foo7":"2018-06-01 00:00:01","foo8":"2018-07-02 00:00:01","foo6":"2019-05-01 00:00:01"}');

        $array = [
            "foo1" => 1, // 0
            "foo6" => 10.1, // 2
            "foo2" => 100.2, // 4
            "foo7" => 11, // 3
            "foo8" => 2, // 1
            "foo3" => 111, // 5
        ];
        ArrayUtils::sort($array, null, [[SORT_NUMERIC, SORT_ASC]]);
        $this->assertEqualArray($array,
            '{"foo1":1,"foo8":2,"foo6":10.1,"foo7":11,"foo2":100.2,"foo3":111}');

        $this->assertTrue(ArrayUtils::keyExists(['foo' => null], "foo"));
        $this->assertTrue(ArrayUtils::keyExists(['foo' => ['foo2' => ['foo3' => null]]], "foo[foo2][foo3]"));
    }

    /**
     * Assert equal array
     * @param $array
     * @param $expected
     */
    private function assertEqualArray($array, $expected)
    {
        $this->assertEquals($expected, json_encode($array));
    }

    /**
     * Stringify array
     * @param $array
     */
    private function stringifyArray(&$array): void
    {
        foreach ($array as $key => $value) {
            $array[$key] = $value instanceof DateTime ? $value->format("Y-m-d H:i:s") : $value;
        }
    }
}
