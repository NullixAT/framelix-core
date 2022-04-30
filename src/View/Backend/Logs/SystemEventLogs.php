<?php

namespace Framelix\Framelix\View\Backend\Logs;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Html\Tabs;
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
    protected string|bool $accessRole = "admin";

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getGet('clear')) {
            $objects = SystemEventLog::getByCondition();
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
        if ($category = Request::getGet('category')) {
            $meta = new \Framelix\Framelix\StorableMeta\SystemEventLog(new SystemEventLog());
            $meta->showSearchAndTableInTabs(SystemEventLog::getByCondition('category = {0}', [$category]));
            return;
        }
        $logs = SystemEventLog::getByCondition(sort: "-id", limit: 2000);
        $logCategories = Mysql::get()->fetchColumn(
            "SELECT DISTINCT(category) FROM `" . SystemEventLog::class . "` ORDER BY category"
        );
        if (!$logs) {
            ?>
            <p class="framelix-alert"><?= Lang::get('__framelix_view_backend_logs_nologs__') ?></p>
            <?php
            return;
        }
        ?>
        <a href="<?= Url::create()->setParameter('clear', 1) ?>"
           class="framelix-button"><?= Lang::get('__framelix_view_backend_logs_clear__') ?></a>
        <div class="framelix-spacer"></div>
        <?php

        $tabs = new Tabs();
        foreach ($logCategories as $logCategory) {
            $tabs->addTab(
                'category-' . $logCategory,
                '__framelix_systemeventlog_' . $logCategory . '__',
                new self(),
                ['category' => $logCategory]
            );
        }
        $tabs->show();
    }
}