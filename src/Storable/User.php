<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\View;

use function array_search;
use function explode;
use function in_array;
use function is_array;
use function password_hash;
use function password_verify;
use function strlen;

/**
 * User
 * @property string $email
 * @property string|null $password
 * @property bool $flagLocked
 * @property mixed|null $roles
 * @property string|null $twoFactorSecret
 * @property mixed|null $twoFactorBackupCodes
 */
class User extends StorableExtended
{
    /**
     * Internal cache
     * @var array
     */
    private static array $cache = [];

    /**
     * Get current logged-in user
     * @param bool $originalUser If simulated user is active, then return original instead of simulated user
     * @return self|null
     */
    public static function get(bool $originalUser = false): ?self
    {
        $key = "getuser-" . (int)$originalUser;
        if (ArrayUtils::keyExists(self::$cache, $key)) {
            return self::$cache[$key];
        }
        $token = UserToken::getByCookie();
        self::$cache[$key] = null;
        if ($token?->user) {
            self::$cache[$key] = $originalUser ? $token->user : $token->simulatedUser ?? $token->user;
        }
        return self::$cache[$key];
    }

    /**
     * Set current logged-in user
     * Is required when some api require a user and set it to a system user
     * @param User|null $user
     */
    public static function setCurrentUser(?User $user): void
    {
        if ($user === null) {
            unset(self::$cache["getuser-0"], self::$cache["getuser-1"]);
            return;
        }
        self::$cache["getuser-0"] = $user;
        self::$cache["getuser-1"] = $user;
    }

    /**
     * Check if given user has any of the given roles
     * @param mixed $roles
     * @param User|false|null $user On false, automatically use the user returned by User::get()
     * @return bool
     */
    public static function hasRole(mixed $roles, User|bool|null $user = false): bool
    {
        if ($roles === "*") {
            return true;
        }
        if ($user === false) {
            $user = self::get();
        }
        if ($roles === false) {
            return !$user;
        }
        if ($roles === true) {
            return !!$user;
        }
        if (!$user || !$user->roles) {
            return false;
        }
        if (!is_array($roles)) {
            $roles = explode(",", $roles);
        }
        foreach ($roles as $role) {
            $role = trim($role);
            if (!strlen($role)) {
                continue;
            }
            if (in_array($role, $user->roles)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get by email
     * @param string $email
     * @param bool $ignoreLocked
     * @return self|null
     */
    public static function getByEmail(string $email, bool $ignoreLocked = false): ?self
    {
        $condition = "email = {0}";
        if (!$ignoreLocked) {
            $condition .= " && flagLocked = 0";
        }
        return self::getByConditionOne($condition, [$email]);
    }

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['roles']->lazyFetch = true;
        $selfStorableSchema->addIndex('email', 'unique');
    }

    /**
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return View::getUrl(View\Backend\User\Index::class)->setParameter('id', $this);
    }

    /**
     * Add a role
     * @param string $role
     */
    public function addRole(string $role): void
    {
        $roles = $this->roles ?? [];
        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $this->roles = $roles;
        }
    }

    /**
     * Add a role
     * @param string $role
     */
    public function removeRole(string $role): void
    {
        $roles = $this->roles ?? [];
        $key = array_search($role, $roles);
        if ($key !== false) {
            unset($roles[$key]);
            $this->roles = $roles;
        }
    }

    /**
     * Set password
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify given password
     * @param string $password
     * @return bool
     */
    public function passwordVerify(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return $this->email ?? '';
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this->email ?? '';
    }
}