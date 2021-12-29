<?php

namespace Framelix\Framelix\Utils;

use JetBrains\PhpStorm\ArrayShape;

use function base_convert;
use function str_pad;
use function substr;

use const STR_PAD_LEFT;

/**
 * Color utils
 */
class ColorUtils
{
    /**
     * Invert given hex color
     * This returns black/white hex color, depending in given background color
     * @link https://stackoverflow.com/a/35970186/1887622
     * @param string $hex
     * @param bool $blackWhite
     * @return string
     */
    public static function invertColor(string $hex, bool $blackWhite = false): string
    {
        $rgb = self::hexToRgb($hex);
        if ($blackWhite) {
            // https://stackoverflow.com/a/3943023/112731
            return ($rgb[0] * 0.299 + $rgb[1] * 0.587 + $rgb[2] * 0.114) > 186 ? '#000000' : '#ffffff';
        }
        // invert color components
        $r = base_convert((255 - $rgb[0]), 10, 16);
        $g = base_convert((255 - $rgb[1]), 10, 16);
        $b = base_convert((255 - $rgb[2]), 10, 16);
        return '#' . str_pad($r, 2, "0", STR_PAD_LEFT) . str_pad($g, 2, "0", STR_PAD_LEFT) . str_pad(
                $b,
                2,
                "0",
                STR_PAD_LEFT
            );
    }

    /**
     * HSL to RGB
     * @param float|int $h Between 0-360
     * @param float|int $s Between 0-1
     * @param float|int $l Between 0-1
     * @return array
     */
    #[ArrayShape(["int", "int", "int"])]
    public static function hslToRgb(float|int $h, float|int $s, float|int $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
        $m = $l - ($c / 2);

        if ($h < 60) {
            $r = $c;
            $g = $x;
            $b = 0;
        } elseif ($h < 120) {
            $r = $x;
            $g = $c;
            $b = 0;
        } elseif ($h < 180) {
            $r = 0;
            $g = $c;
            $b = $x;
        } elseif ($h < 240) {
            $r = 0;
            $g = $x;
            $b = $c;
        } elseif ($h < 300) {
            $r = $x;
            $g = 0;
            $b = $c;
        } else {
            $r = $c;
            $g = 0;
            $b = $x;
        }

        $r = ($r + $m) * 255;
        $g = ($g + $m) * 255;
        $b = ($b + $m) * 255;

        return [(int)round($r), (int)round($g), (int)round($b)];
    }

    /**
     * RGB to HSL
     * @param int $r
     * @param int $g
     * @param int $b
     * @return array
     */
    #[ArrayShape(["int 0-360", "float 0-1", "float 0-1"])]
    public static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $h = 0;
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0) {
            $h = $s = 0; // achromatic
        } else {
            $s = $d / (1 - abs(2 * $l - 1));
            switch ($max) {
                case $r:
                    $h = 60 * fmod((($g - $b) / $d), 6);
                    if ($b > $g) {
                        $h += 360;
                    }
                    break;

                case $g:
                    $h = 60 * (($b - $r) / $d + 2);
                    break;

                case $b:
                    $h = 60 * (($r - $g) / $d + 4);
                    break;
            }
        }
        return [
            (int)round($h),
            round($s, 3),
            round($l, 3)
        ];
    }

    /**
     * RGB to hex
     * @param int $r
     * @param int $g
     * @param int $b
     * @return string
     */
    public static function rgbToHex(int $r, int $g, int $b): string
    {
        return '#' . substr(base_convert(((1 << 24) + ($r << 16) + ($g << 8) + $b), 10, 16), 1);
    }

    /**
     * Hex to rgb
     * @link https://stackoverflow.com/a/15202130/1887622
     * @param string $hex
     * @return array
     */
    #[ArrayShape(["int", "int", "int"])]
    public static function hexToRgb(string $hex): array
    {
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return [(int)$r, (int)$g, (int)$b];
    }
}