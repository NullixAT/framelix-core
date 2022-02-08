<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Storable\Mutex;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\SystemEventLog;

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

        // delete system value logs older then 6 months
        $logs = SystemEventLog::getByCondition('DATE(createTime) <= {0}', [Date::create("now -  6 months")]);
        Storable::deleteMultiple($logs);

        // delete cron system value logs older then 2 week
        $logs = SystemEventLog::getByCondition(
            'DATE(createTime) <= {0} && category = {1}',
            [Date::create("now - 2 weeks"), SystemEventLog::CATEGORY_CRON]
        );
        Storable::deleteMultiple($logs);
    }
}