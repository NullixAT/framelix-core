<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;

/**
 * User logout view
 */
class Logout extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * On request
     */
    public function onRequest(): void
    {
        UserToken::getByCookie()?->delete();
        UserToken::setCookieValue(null);
        Url::getApplicationUrl()->redirect();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
    }


}