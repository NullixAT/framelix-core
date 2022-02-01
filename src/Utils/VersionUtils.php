<?php

namespace Framelix\Framelix\Utils;

use function explode;
use function preg_match;

/**
 * Version utilities for frequent tasks
 */
class VersionUtils
{
    /**
     * Split version string
     * Example: 4.3.2, 4.3.2beta1
     * @param string $versionString
     * @return array{major:int|null, minor:int|null, patch:int|null, devBranch:string|null, devVersion:int|null}
     */
    public static function splitVersionString(string $versionString): array
    {
        $arr = ['major' => null, 'minor' => null, 'patch' => null, 'devBranch' => null, 'devVersion' => null];
        $exp = explode(".", $versionString);
        if (isset($exp[0])) {
            $arr['major'] = (int)$exp[0];
        }
        if (isset($exp[1])) {
            $arr['minor'] = (int)$exp[1];
        }
        if (isset($exp[2])) {
            preg_match("~(^[0-9]+)([a-z]*)([0-9]*)~i", $exp[2], $match);
            if ($match) {
                $arr['patch'] = (int)$match[1];
                if ($match[2]) {
                    $arr['devBranch'] = $match[2];
                }
                if ($match[3]) {
                    $arr['devVersion'] = (int)$match[3];
                }
            }
        }
        return $arr;
    }
}