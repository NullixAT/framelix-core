<?php

namespace Framelix\Framelix\View\Backend\Logs;

use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;

use function basename;
use function reset;
use function unlink;

use const SCANDIR_SORT_DESCENDING;

/**
 * Log viewer for application error logs
 */
class ErrorLogs extends View
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
            $files = FileUtils::getFiles(FRAMELIX_APP_ROOT . "/logs", sortOrder: SCANDIR_SORT_DESCENDING);
            $count = 0;
            foreach ($files as $file) {
                if (basename($file) !== ".gitignore") {
                    unlink($file);
                    $count++;
                }
            }
            Toast::success('Deleted ' . $count . ' logs');
            \Framelix\Framelix\View::getUrl(self::class)->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $files = FileUtils::getFiles(
            FRAMELIX_APP_ROOT . "/logs",
            "~\.php$~",
            sortOrder: SCANDIR_SORT_DESCENDING
        );
        if (!$files) {
            ?>
            <p class="framelix-alert"><?= Lang::get('__framelix_view_backend_logs_nologs__') ?></p>
            <?php
            return;
        }
        $firstFile = basename(reset($files));
        ?>
        <a href="<?= Url::create()->setParameter('clear', 1) ?>"
           class="framelix-button"><?= Lang::get('__framelix_view_backend_logs_clear__') ?></a>
        <?php
        foreach ($files as $file) {
            Buffer::start();
            require $file;
            $contents = Buffer::get();
            ErrorHandler::showErrorFromExceptionLog(JsonUtils::decode($contents), true);
        }
    }
}