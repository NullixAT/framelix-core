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
     * @param string $versionString
     * @return array{major:int|null, minor:int|null, patch:int|null}
     */
    public static function splitVersionString(string $versionString): array
    {
        $arr = ['major' => null, 'minor' => null, 'patch' => null];
        $exp = explode(".", $versionString);
        if (isset($exp[0])) {
            $arr['major'] = (int)$exp[0];
        }
        if (isset($exp[1])) {
            $arr['minor'] = (int)$exp[1];
        }
        if (isset($exp[2])) {
            preg_match("~(^[0-9]+)(.*)~", $exp[2], $match);
            if ($match) {
                $arr['patch'] = (int)$match[1];
            }
        }
        return $arr;
    }
}