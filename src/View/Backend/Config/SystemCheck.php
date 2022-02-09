<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Config;
use Framelix\Framelix\Console;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use function escapeshellarg;
use function escapeshellcmd;
use function realpath;

/**
 * SystemCheck
 */
class SystemCheck extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,configuration";

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $checks = ['versions', 'https', 'cron'];
        foreach ($checks as $check) {
            $valid = false;
            $subInfo = '';
            switch ($check) {
                case 'versions':
                    $valid = true;
                    $packageJson = JsonUtils::getPackageJson(null);
                    $subInfo = '<b>Application Version: ' . ($packageJson['version'] ?? "-") . "</b><br/>";
                    $modules = scandir(FRAMELIX_APP_ROOT . "/modules");
                    foreach ($modules as $module) {
                        if (str_starts_with($module, ".")) {
                            continue;
                        }
                        $path = FRAMELIX_APP_ROOT . "/modules/" . $module;
                        if (!is_dir($path)) {
                            continue;
                        }
                        $packageJson = JsonUtils::getPackageJson($module);
                        if (!$packageJson) {
                            continue;
                        }
                        $subInfo .= 'Module "' . $module . '" Version ' . (!isset(Config::$loadedModules[$module]) ? '(Not enabled)' : '') . ' ' . ($packageJson['version'] ?? "-") . "<br/>";

                    }
                    break;
                case 'https':
                    $valid = Config::get('applicationHttps');
                    break;
                case 'cron':
                    $event = SystemEventLog::getByConditionOne(
                        'category = {0} && createTime >= {1}',
                        [SystemEventLog::CATEGORY_CRON, DateTime::create('now - 10 minutes')],
                        "-id"
                    );
                    $valid = !!$event;
                    if (!$valid) {
                        $subInfo = '*/5 * * * * ' . escapeshellcmd(
                                Config::get('shellAliases[php]')
                            ) . ' ' . escapeshellarg(realpath(Console::CONSOLE_SCRIPT)) . ' cron';
                    } else {
                        $subInfo = "Last run: " . $event->createTime->getHtmlString();
                    }
                    break;
            }
            ?>
            <div class="framelix-alert framelix-alert-<?= $valid ? 'success' : 'error' ?>">
                <div>
                    <?= Lang::get('__framelix_view_backend_config_systemcheck_' . $check . '__') ?>
                    <?php
                    if ($subInfo) {
                        echo '<div>' . $subInfo . '</div>';
                    }
                    if (!$valid) {
                        echo '<div>' . Lang::get(
                                '__framelix_view_backend_config_systemcheck_' . $check . '_error__'
                            ) . '</div>';
                    }
                    ?>
                </div>

            </div>
            <?php
        }
    }
}