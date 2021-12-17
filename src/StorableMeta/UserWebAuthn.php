<?php

namespace Framelix\Framelix\StorableMeta;

use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

/**
 * UserWebAuthn
 */
class UserWebAuthn extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\Framelix\Storable\UserWebAuthn
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty("deviceName");
        $property->addDefaultField();

        $this->addDefaultPropertiesAtEnd();
    }
}