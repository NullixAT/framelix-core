<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Url;
use Framelix\Framelix\View;

/**
 * UserWebAuthn
 * @property User $user
 * @property string $deviceName
 * @property mixed|null $challenge
 * @property mixed|null $authData
 */
class UserWebAuthn extends StorableExtended
{
    /**
     * Is this storable editable
     * @return bool
     */
    public function isEditable(): bool
    {
        return true;
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }

    /**
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return View::getUrl(View\Backend\UserProfile\Index::class)->setParameter(
            'editWebAuthn',
            $this
        )->setHash('tabs:fido2');
    }

}