<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Utils\JsonUtils;

use function date;
use function file_exists;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function substr;
use function var_dump;

use const FRAMELIX_APP_ROOT;

/**
 * Cron Runner
 */
class Cron extends Console
{
    /**
     * Run cronjob
     */
    public static function runCron(): void
    {
        // every hour check for updates
        if ((int)date("i") <= 0 || self::getParameter('forceUpdateCheck')) {
            $packageJsonFile = FRAMELIX_APP_ROOT . "/package.json";
            if (file_exists($packageJsonFile)) {
                $packageJson = JsonUtils::readFromFile($packageJsonFile);
                if (isset($packageJson['repository']['url'])) {
                    $url = $packageJson['repository']['url'];
                    if (str_starts_with($url, "git+") || str_contains($url, "github.com")) {
                        if (str_starts_with($url, "git+")) {
                            $url = substr($url, 4);
                        }
                        if (str_ends_with($url, ".git")) {
                            $url = substr($url, 0, -4);
                        }
                    }
                }
            }
        }
    }
}