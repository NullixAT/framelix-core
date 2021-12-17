<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Config;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Utils\RandomGenerator;

use function array_key_exists;
use function str_replace;

use const FRAMELIX_MODULE;

/**
 * User Token
 * @property User $user
 * @property User|null $simulatedUser
 * @property string $token
 */
class UserToken extends StorableExtended
{
    /**
     * Internal cache
     * @var array
     */
    private static array $cache = [];

    /**
     * Create a new, already stored, token for given user
     * @param User $user
     * @return self
     */
    public static function create(User $user): self
    {
        $token = RandomGenerator::getRandomString(32, 64);
        while (self::getForToken($token)) {
            $token = RandomGenerator::getRandomString(32, 64);
        }
        $instance = new self();
        $instance->token = $token;
        $instance->user = $user;
        $instance->store();
        return $instance;
    }

    /**
     * Set the token cookie
     * @param string|null $token
     * @param int|null $lifetime Lifetime in seconds from now + given seconds, null then lifetime is a browser session
     */
    public static function setCookieValue(?string $token, ?int $lifetime = null): void
    {
        Cookie::set(
            str_replace("{module}", FRAMELIX_MODULE, Config::get('userTokenCookieName')),
            $token,
            true,
            $lifetime
        );
    }

    /**
     * Get token cookie value
     * @return string|null
     */
    public static function getCookieValue(): ?string
    {
        return Cookie::get(str_replace("{module}", FRAMELIX_MODULE, Config::get('userTokenCookieName')));
    }

    /**
     * Get token by the cookie token value
     * @return self|null
     */
    public static function getByCookie(): ?self
    {
        $cacheKey = __METHOD__;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        self::$cache[$cacheKey] = self::getForToken(self::getCookieValue());
        return self::$cache[$cacheKey];
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