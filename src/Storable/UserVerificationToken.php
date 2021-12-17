<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Utils\RandomGenerator;

/**
 * User verification token
 * @property int $category
 * @property User $user
 * @property string $token
 * @property mixed|null $additionalData
 */
class UserVerificationToken extends StorableExtended
{
    public const CATEGORY_FORGOT_PASSWORD = 1;
    public const CATEGORY_CHANGE_EMAIL_OLD = 2;
    public const CATEGORY_CHANGE_EMAIL_NEW = 3;

    /**
     * Create a new, already stored, token for given user
     * @param User $user
     * @param int $category
     * @param mixed $additionalData
     * @return self
     */
    public static function create(User $user, int $category, mixed $additionalData = null): self
    {
        $token = RandomGenerator::getRandomString(32, 64);
        while (self::getForToken($token)) {
            $token = RandomGenerator::getRandomString(32, 64);
        }
        $instance = new self();
        $instance->token = $token;
        $instance->user = $user;
        $instance->category = $category;
        $instance->additionalData = $additionalData;
        $instance->store();
        return $instance;
    }

    /**
     * Get instance for the given token
     * @param string|null $token
     * @return self|null
     */
    public static function getForToken(?string $token): ?self
    {
        if (!$token) {
            return null;
        }
        return self::getByConditionOne('token = {0} && user.flagLocked = 0', [$token]);
    }

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->addIndex('token', 'unique');
    }

    /**
     * Is deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }
}