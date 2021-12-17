<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\StorableMeta\User;
use Framelix\Framelix\View\Backend\View;

/**
 * Search view for user
 */
class Search extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,usermanagement";

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
        $meta = new User(new \Framelix\Framelix\Storable\User());
        $meta->getQuickSearch()->show();
    }
}