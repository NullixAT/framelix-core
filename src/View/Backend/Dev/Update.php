<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\View\Backend\View;

/**
 * Updating sources and database
 */
class Update extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,dev";

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
        $tabs->addTab('update-source', null, new UpdateSourceCode());
        $tabs->show();
    }
}