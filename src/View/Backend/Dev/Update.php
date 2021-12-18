<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\View\Backend\View;

use function is_dir;

use const FRAMELIX_APP_ROOT;

/**
 * Updating sources and database
 */
class Update extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "dev";

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
        $tabs = new Tabs();
        $tabs->addTab('update-database', null, new UpdateDatabase());
        if (is_dir(FRAMELIX_APP_ROOT . "/.svn")) {
            $tabs->addTab('update-source', null, new UpdateSourceCode());
        }
        $tabs->show();
    }
}