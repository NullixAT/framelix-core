<?php

namespace Framelix\Framelix\View\Backend\Logs;

use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

/**
 * Log viewer for application error logs
 */
class SystemEventLogs extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,logs";

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getGet('clear')) {
            $objects = SystemEventLog::getByCondition('id <= {0}', [Request::getGet('clear')]);
            Storable::deleteMultiple($objects);
            Toast::success('Deleted ' . count($objects) . ' logs');
            \Framelix\Framelix\View::getUrl(self::class)->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $logs = SystemEventLog::getByCondition(sort: "-id", limit: 2000);
        if (!$logs) {
            ?>
            <p class="framelix-alert"><?= Lang::get('__framelix_view_backend_logs_nologs__') ?></p>
            <?
            return;
        }
        $lastLog = reset($logs);
        ?>
        <a href="<?= Url::create()->setParameter('clear', $lastLog) ?>"
           class="framelix-button"><?= Lang::get('__framelix_view_backend_logs_clear__') ?></a>
        <div class="framelix-spacer"></div>
        <?
        $meta = new \Framelix\Framelix\StorableMeta\SystemEventLog(new SystemEventLog());
        $meta->showSearchAndTableInTabs(SystemEventLog::getByCondition());
    }
}