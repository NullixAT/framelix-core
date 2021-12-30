<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;

use function array_key_exists;
use function array_keys;
use function array_shift;
use function array_values;
use function call_user_func_array;
use function count;
use function explode;
use function is_array;
use function is_object;
use function mb_strtolower;
use function method_exists;
use function str_replace;

/**
 * Array utilities for frequent tasks
 */
class ArrayUtils
{
    /**
     * Get a array to use in javascript to keep original sort of the given array
     * In JS non real arrays (objects) will have undefined sorting of properties, which may cause different sort
     * of properties in frontend then in backend
     * This will create an array with keys/values separated in correct order
     * @param array $arr
     * @return array ["keys" => [...], "values" => [...]]
     */
    public static function getArrayForJavascript(array $arr): array
    {
        return [
            "type" => "preparedArray",
            "keys" => array_keys($arr),
            "values" => array_values($arr)
        ];
    }

    /**
     * Sort given array by using given keys as value to sort by
     * @param array|null $array
     * @param string[]|string|null $keys If null, than use array value. If given, sort by given keys values, can also be object methods, see self::getValue() for all possible options
     * @param int[]|int[][] $sortOptions One sort option for each key in $keys. For numeric DESC use [SORT_DESC, SORT_NUMERIC]
     * @param mixed $keyParameters If key is expected to be a method, pass the given parameters, see self::getValue() for all possible options
     */
    public static function sort(
        array|null &$array,
        array|string|null $keys,
        array $sortOptions,
        mixed $keyParameters = null
    ): void {
        if (!$array) {
            return;
        }
        $callParameters = [];

        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $keyIndex => $keyName) {
            $values = [];
            foreach ($array as $arrayKey => $row) {
                if ($keyName === null) {
                    $value = $row;
                } else {
                    $value = self::getValue($row, $keyName, $keyParameters[$keyIndex] ?? null);
                }
                $value = StringUtils::stringify($value, toStringMethods: ["getSortableValue", "getRawTextString"]);
                $value = StringUtils::slugify($value);
                $value = mb_strtolower($value);
                $values[$arrayKey] = $value;
            }

            if ($keyName === null) {
                $sortFlag = reset($sortOptions);
            } else {
                $sortFlag = $sortOptions[$keyIndex] ?? null;
            }
            if ($sortFlag === null) {
                throw new Exception(
                    "Sort flag for array key '$keyIndex' is not set",
                    ErrorCode::ARRAYUTILS_SORT_FLAG_MISSING
                );
            }
            if (!is_array($sortFlag)) {
                $sortFlag = [$sortFlag];
            }
            $callParameters[] = $values;
            foreach ($sortFlag as $flag) {
                $callParameters[] = $flag;
            }
        }
        $keys = array_keys($array);
        $callParameters[] = &$array;
        $callParameters[] = &$keys;
        call_user_func_array("array_multisort", $callParameters);
        $array = array_combine($keys, $array);
    }

    /**
     * Join keys to create a selector like foo[bar][depth][deeper]
     * This is the counterpart to splitKeyString()
     * @param array $keys
     * @return string
     */
    public static function joinKeys(array $keys): string
    {
        $str = array_shift($keys);
        foreach ($keys as $key) {
            $str .= "[$key]";
        }
        return $str;
    }

    /**
     * Split a key string into array parts for each depts
     * @param string|string[]|int|int[] $key example: foo[bar][depth][deeper]
     * @return array example: ["foo", "bar", "depth", "deeper"]
     */
    public static function splitKeyString(array|string|int $key): array
    {
        if (is_array($key)) {
            return $key;
        }
        return explode("[", str_replace("]", "", $key));
    }

    /**
     * Checks if a given key exists, even if it is null
     * @param mixed $array The array to check against
     * @param string|string[]|int|int[] $key Could be a key/method in any depth, example: foo[bar][depth][deeper] or ['foo', 'bar', 'depth', 'deeper']
     * @return bool
     */
    public static function keyExists(mixed $array, array|string|int $key): bool
    {
        if (!is_array($array)) {
            return false;
        }
        $levels = self::splitKeyString($key);
        $workingValue = $array;
        foreach ($levels as $levelKey) {
            if (!is_array($workingValue) || !array_key_exists($levelKey, $workingValue)) {
                return false;
            }
            $workingValue = $workingValue[$levelKey];
        }
        return true;
    }


    /**
     * Set a value in an array in any depth
     * @param mixed $arr The array reference to set the value into, this array will be modified
     * @param string|string[] $key Could be a key in any depth, example: foo[bar][depth][deeper]
     * @param mixed $value The value to set
     */
    public static function setValue(mixed &$arr, array|string $key, mixed $value): void
    {
        $levels = self::splitKeyString($key);
        $firstLevel = array_shift($levels);
        if (!count($levels)) {
            $arr[$firstLevel] = $value;
        } else {
            if (!isset($arr[$firstLevel])) {
                $arr[$firstLevel] = [];
            }
            self::setValue($arr[$firstLevel], $levels, $value);
        }
    }

    /**
     * Get a value from any possible object/array
     * Return null if key not found
     * @param mixed $array Get value from given array
     * @param string|string[] $key Could be a key/method in any depth, example: foo[bar][depth][deeper] or ['foo', 'bar', 'depth', 'deeper']
     * @param array|null $params If key is expected to be a method, pass the given parameters
     * @return mixed
     */
    public static function getValue(mixed $array, array|string $key, ?array $params = null): mixed
    {
        $levels = self::splitKeyString($key);
        $workingValue = $array;
        foreach ($levels as $levelKey) {
            if (is_array($workingValue) && array_key_exists($levelKey, $workingValue)) {
                $workingValue = $workingValue[$levelKey];
            } elseif (is_object($workingValue) && method_exists($workingValue, $levelKey)) {
                $workingValue = call_user_func_array([$workingValue, $levelKey], $params ?? []);
            } elseif (is_object($workingValue)) {
                $workingValue = $workingValue->{$levelKey} ?? null;
            } else {
                return null;
            }
        }
        return $workingValue;
    }

    /**
     * Get an array of values with given key from any possible array
     * @param array|null $array Map values from this array
     * @param mixed $keyForValue Could be a key/method in any depth, example: foo[bar][depth][deeper] or ['foo', 'bar', 'depth', 'deeper']
     * @param mixed $keyForIndex Could be a key/method in any depth, example: foo[bar][depth][deeper] or ['foo', 'bar', 'depth', 'deeper']
     * @return array
     */
    public static function map(?array $array, mixed $keyForValue, mixed $keyForIndex = null): array
    {
        $out = [];
        if (!is_array($array)) {
            return $out;
        }
        foreach ($array as $value) {
            $v = self::getValue($value, $keyForValue);
            if ($keyForIndex !== null) {
                $index = self::getValue($value, $keyForIndex);
                if ($index === null) {
                    continue;
                }
                $out[$index] = $v;
            } else {
                $out[] = $v;
            }
        }
        return $out;
    }

    /**
     * Merge arrays together from left to right
     * Return a new array
     * @param mixed ...$arrays
     * @return array
     */
    public static function merge(...$arrays): array
    {
        $return = [];
        foreach ($arrays as $array) {
            if (!is_array($array)) {
                continue;
            }
            foreach ($array as $key => $value) {
                $return[$key] = is_array($value) ? self::merge($return[$key] ?? null, $value) : $value;
            }
        }
        return $return;
    }
}