<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Lang;

use function call_user_func_array;
use function method_exists;
use function str_replace;

/**
 * String utilities for frequent tasks
 */
class StringUtils
{
    /**
     * Convert any given value to a string
     * Could be array, object, string, int, etc...
     * NULL values will be ignored/empty string
     * Boolean will be translated yes/no
     * Float will be converted with NumberUtils
     * Objects will use specific convert method or __toString
     * Array will be flattened and values will be concated with $arrayConcatChar
     * @param mixed $anyValue
     * @param string $arrayConcatChar If array is given, concat with this character
     * @param string[] $toStringMethods Methods to use to convert an object to string
     * @return string
     */
    public static function stringify(
        mixed $anyValue,
        string $arrayConcatChar = ", ",
        array $toStringMethods = ["getRawTextString"]
    ): string {
        // this cases are always empty strings
        if ($anyValue === null || (is_array($anyValue) && !$anyValue)) {
            return "";
        }
        if (is_array($anyValue)) {
            $arr = [];
            foreach ($anyValue as $v) {
                $v = self::stringify($v, $arrayConcatChar, $toStringMethods);
                if ($v !== "") {
                    $arr[] = $v;
                }
            }
            return implode($arrayConcatChar, $arr);
        }
        if (is_string($anyValue)) {
            return $anyValue;
        }
        if (is_float($anyValue)) {
            if ($anyValue - (int)$anyValue != 0) {
                return NumberUtils::format($anyValue, 2);
            }
            return (string)(int)$anyValue;
        }
        if (is_bool($anyValue)) {
            return Lang::get($anyValue ? "__framelix_yes__" : "__framelix_no__");
        }
        if (is_object($anyValue)) {
            foreach ($toStringMethods as $stringMethod) {
                if (method_exists($anyValue, $stringMethod)) {
                    return call_user_func_array([$anyValue, $stringMethod], []);
                }
            }
        }
        return (string)$anyValue;
    }

    /**
     * Creates a slug out of a string.
     * Replaces everything but letters and numbers with dashes.
     * @see http://en.wikipedia.org/wiki/Slug_(typesetting)
     * @param string $string The string to slugify.
     * @param bool $replaceSpaces
     * @param bool $replaceDots
     * @return string A search-engine friendly string that is safe
     *   to be used in URLs.
     */
    public static function slugify(string $string, bool $replaceSpaces = true, bool $replaceDots = true): string
    {
        $s = ["Ö", "Ü", "Ä", "ö", "ü", "ä", "ß"];
        $r = ["Oe", "Ue", "Ae", "oe", "ue", "ae", "ss"];
        if ($replaceSpaces) {
            $s[] = " ";
            $r[] = "-";
        }
        if ($replaceDots) {
            $s[] = ".";
            $r[] = "-";
        }
        $string = str_replace($s, $r, $string);
        $string = preg_replace("~[^a-z0-9\. \-_]~i", "-", $string);
        return preg_replace("~-{2,}~i", "-", $string);
    }
}