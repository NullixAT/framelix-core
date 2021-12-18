<?php

namespace Framelix\Framelix\View\Backend\Logs;

use Framelix\Framelix\Error;
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
    protected string|bool $accessRole = "admin,logs";

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getGet('clear')) {
            $files = FileUtils::getFiles(FileUtils::getAppRootPath() . "/logs", sortOrder: SCANDIR_SORT_DESCENDING);
            $delete = false;
            $count = 0;
            foreach ($files as $file) {
                if (basename($file) === Request::getGet('clear')) {
                    $delete = true;
                }
                if ($delete) {
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
        $files = FileUtils::getFiles(FileUtils::getAppRootPath() . "/logs", "~\.php$~", sortOrder: SCANDIR_SORT_DESCENDING);
        if (!$files) {
            ?>
            <p class="framelix-alert"><?= Lang::get('__framelix_view_backend_logs_nologs__') ?></p>
            <?
            return;
        }
        $firstFile = basename(reset($files));
        reset($files);
        ?>
        <a href="<?= Url::create()->setParameter('clear', $firstFile) ?>"
           class="framelix-button"><?= Lang::get('__framelix_view_backend_logs_clear__') ?></a>
        <?
        foreach ($files as $file) {
            Buffer::start();
            require $file;
            $contents = Buffer::get();
            Error::showErrorFromExceptionLog(JsonUtils::decode($contents), true);
        }
    }
}