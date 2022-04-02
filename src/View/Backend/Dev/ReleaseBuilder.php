<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\View\Backend\View;

/**
 * ReleaseBuilder
 */
class ReleaseBuilder extends View
{
    public const BUILD_FOLDER = __DIR__ . "/../../../../build";
    public const DIST_FOLDER = __DIR__ . "/../../../../build/dist";

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

    /**
     * Only in dev mode
     * @var bool
     */
    protected bool $devModeOnly = true;

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getGet('createDist')) {
            $distFiles = FileUtils::getFiles(self::DIST_FOLDER, "~\.zip$~");
            foreach ($distFiles as $distFile) {
                unlink($distFile);
            }
            $shell = Shell::prepare("php {*}", [self::BUILD_FOLDER . "/create-release-package.php"]);
            $shell->execute();;
            Toast::success('__framelix_view_backend_dev_releasebuilder_created__');
            $this->getSelfUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        ?>
        <a href="?createDist=1" class="framelix-button"><?= Lang::get('__framelix_view_backend_dev_releasebuilder_create__') ?></a>
        <div class="framelix-spacer"></div>
        <?php
        $distFiles = FileUtils::getFiles(self::DIST_FOLDER, "~\.zip$~");
        foreach ($distFiles as $distFile) {
            ?>
            <div>
                <?= FileUtils::normalizePath($distFile, true) ?>
            </div>
            <?php
        }
    }
}