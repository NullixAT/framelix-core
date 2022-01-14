<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\View\Backend\View;

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
        $checks = ['https'];
    }
}