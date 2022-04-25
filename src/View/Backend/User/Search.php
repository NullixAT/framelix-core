<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Db\Mysql;
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
        $userCount = Mysql::get()->fetchOne('SELECT COUNT(*) FROM `' . \Framelix\Framelix\Storable\User::class . '`');
        $meta = new User(new \Framelix\Framelix\Storable\User());
        $quickSearch = $meta->getQuickSearch();
        $quickSearch->forceInitialQuery = $userCount <= 50 ? "*" : null;
        $quickSearch->show();
    }
}