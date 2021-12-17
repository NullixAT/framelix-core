<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;

/**
 * CancelSimulation
 */
class CancelSimulation extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $token = UserToken::getByCookie();
        $token->simulatedUser = null;
        $token->store();
        Toast::success('__framelix_simulateuser_canceled__');
        Url::create(Request::getGet('redirect') ?? Url::getApplicationUrl())->redirect();
    }
}