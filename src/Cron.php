<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Storable\Mutex;

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
        // update check every hour
        if (self::getParameter('forceUpdateCheck') || !Mutex::isLocked('framelix-cron', 3600)) {
            if (!self::getParameter('forceUpdateCheck')) {
                Mutex::create('framelix-cron');
            }
            self::checkAppUpdate();
        }
    }
}