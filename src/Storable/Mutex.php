<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\StorableSchema;

use function time;

/**
 * Mutex
 * Provide an easy way to lock/release exclusive execution for tasks
 * @property string $name
 * @property DateTime $startTime
 */
class Mutex extends Storable
{
    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->addIndex('name', 'unique');
    }

    /**
     * Create a mutex for given name
     * If mutex already exist, it does update the mutex start time
     * @param string $name
     * @return Mutex
     */
    public static function create(string $name): Mutex
    {
        $mutex = self::getByConditionOne('name = {0}', [$name]);
        if (!$mutex) {
            $mutex = new self();
            $mutex->name = $name;
        }
        $mutex->startTime = DateTime::create('now');
        $mutex->store();
        return $mutex;
    }

    /**
     * Check if a mutex with given name exist
     * @param string $name
     * @param int|null $maxLifetime Seconds from start of mutex - If set, it will return true when mutex is older than max lifetime
     *  Should be used when you not want a deadlock if a job cant finish because of errors and mutex is never released
     * @return bool
     */
    public static function isLocked(string $name, ?int $maxLifetime = null): bool
    {
        $mutex = self::getByConditionOne('name = {0}', [$name]);
        if (!$mutex) {
            return false;
        }
        if ($maxLifetime && $mutex->startTime->getTimestamp() < time() - $maxLifetime) {
            return false;
        }
        return true;
    }

    /**
     * Release a mutex for given name
     * @param string $name
     */
    public static function release(string $name): void
    {
        self::getByConditionOne('name = {0}', [$name])?->delete();
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }
}