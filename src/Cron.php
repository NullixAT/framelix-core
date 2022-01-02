<?php

namespace Framelix\Framelix;

use function date;

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
            self::checkAppUpdates();
        }
    }
}