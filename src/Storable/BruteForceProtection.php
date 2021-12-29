<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;

use function md5;
use function time;

/**
 * BruteForceProtection
 * Very simple ip based brute force protection
 * Choose limits very high to prevent false positive from same networks (Big companies with lots of clients under same ip)
 * @property string $idHash
 * @property int $count
 * @property DateTime $lastCount
 */
class BruteForceProtection extends Storable
{
    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['idHash']->length = 32;
        $selfStorableSchema->addIndex('idHash', 'unique');
    }

    /**
     * Reset status for current ip and id
     * @param string $id
     * @param string|null $connectionId Database connection id to use
     * @return void
     */
    public static function reset(
        string $id,
        ?string $connectionId = null
    ): void {
        $idHash = self::getIdHash($id);
        $entry = self::getByConditionOne('idHash = {0}', [$idHash], connectionId: $connectionId);
        $entry?->delete();
    }

    /**
     * Count up for current ip and given id
     * @param string $id
     * @param string|null $connectionId Database connection id to use
     * @return void
     */
    public static function countUp(
        string $id,
        ?string $connectionId = null
    ): void {
        $idHash = self::getIdHash($id);
        $entry = self::getByConditionOne('idHash = {0}', [$idHash], connectionId: $connectionId);
        if (!$entry) {
            $entry = new self();
            $entry->connectionId = $connectionId;
            $entry->idHash = $idHash;
            $entry->count = 0;
        }
        $entry->count++;
        $entry->lastCount = DateTime::create('now');
        $entry->store();
    }

    /**
     * Check if given current client ip and given id is blocked
     * @param string $id
     * @param bool $addToast If true, it will add an error toast which defined how long the user must wait
     * @param int $blockCountTreshold When count reaches this treshold, it will return false when lastlog was not long enough ago
     * @param int $waitSecondsPerCount A client must wait $waitSecondsPerCount*countsOverTreshold seconds since the last log to make this function return true
     * @param string|null $connectionId Database connection id to use
     * @return bool Return false when is not blocked
     */
    public static function isBlocked(
        string $id,
        bool $addToast = true,
        int $blockCountTreshold = 60,
        int $waitSecondsPerCount = 60,
        ?string $connectionId = null
    ): bool {
        $until = self::getBlockReleaseTime($id, $blockCountTreshold, $waitSecondsPerCount, $connectionId);
        if (!$until) {
            return false;
        }
        if ($addToast) {
            Toast::error(
                Lang::get(
                    '__myself_wait_for_unblock__',
                    [$until->getRelativeTimeUnits(DateTime::create('now'))]
                )
            );
        }
        return true;
    }

    /**
     * Get end time when block is released
     * Returns null if current client ip and given id isn't blocked
     * @param string $id
     * @param int $blockCountTreshold When count exceed this treshold, it will return a time when lastlog was not long enough ago
     * @param int $waitSecondsPerCount A client must wait $waitSecondsPerCount*countsOverTreshold seconds since the last log to make this function return true
     * @param string|null $connectionId Database connection id to use
     * @return DateTime|null
     */
    public static function getBlockReleaseTime(
        string $id,
        int $blockCountTreshold = 60,
        int $waitSecondsPerCount = 60,
        ?string $connectionId = null
    ): ?DateTime {
        $idHash = self::getIdHash($id);
        $entry = self::getByConditionOne('idHash = {0}', [$idHash], connectionId: $connectionId);
        if (!$entry) {
            return null;
        }
        if ($entry->count <= $blockCountTreshold) {
            return null;
        }
        $overTreshold = $entry->count - $blockCountTreshold;
        $until = $entry->lastCount->clone();
        $waitSeconds = $waitSecondsPerCount * $overTreshold;
        $until->modify("+ $waitSeconds seconds");
        if ($until->getTimestamp() <= time()) {
            return null;
        }
        return $until;
    }

    /**
     * Get id hash
     * @param string $id
     * @return string
     */
    private static function getIdHash(string $id): string
    {
        return md5(Request::getClientIp() . "-" . $id);
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