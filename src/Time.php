<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\StorablePropertyInterface;
use Framelix\Framelix\Db\StorableSchemaProperty;

use function explode;
use function floor;
use function is_string;
use function str_pad;

/**
 * Time utilities for frequent tasks
 */
class Time implements StorablePropertyInterface
{
    /**
     * The time in seconds
     * @var int
     */
    public int $seconds;

    /**
     * Setup the property database schema definition to this storable property itself
     * This defines how the column will be created in the database
     * @param StorableSchemaProperty $storableSchemaProperty
     */
    public static function setupSelfStorableSchemaProperty(StorableSchemaProperty $storableSchemaProperty): void
    {
        $storableSchemaProperty->databaseType = "int";
        $storableSchemaProperty->length = 9;
        $storableSchemaProperty->unsigned = true;
    }

    /**
     * Create an instance from the original database value
     * @param mixed $dbValue
     * @return self
     */
    public static function createFromDbValue(mixed $dbValue): self
    {
        $instance = new self();
        $instance->seconds = (int)$dbValue;
        return $instance;
    }

    /**
     * Create an instance from a submitted form value
     * @param mixed $formValue
     * @return self
     */
    public static function createFromFormValue(mixed $formValue): self
    {
        return self::create($formValue);
    }

    /**
     * Create instance from given time string
     * @param string $timeString
     * @return self
     */
    public static function create(string $timeString): self
    {
        $instance = new self();
        $instance->seconds = self::timeStringToSeconds($timeString);
        return $instance;
    }

    /**
     * Convert a time string to hours
     * @param mixed $value
     * @return float
     */
    public static function timeStringToHours(mixed $value): float
    {
        return round(self::timeStringToSeconds($value) / 3600, 4);
    }

    /**
     * Convert a time string to seconds
     * @param mixed $value
     * @return int
     */
    public static function timeStringToSeconds(mixed $value): int
    {
        if (!is_string($value)) {
            return 0;
        }
        $spl = explode(":", $value);
        return ((int)$spl[0] * 3600) + ((int)$spl[1] * 60) + ((int)($spl[2] ?? 0));
    }

    /**
     * Convert hours to time string
     * @param float $hours
     * @param bool includeSeconds
     * @return string
     */
    public static function hoursToTimeString(float $hours, bool $includeSeconds = false): string
    {
        return self::secondsToTimeString(round($hours * 3600), $includeSeconds);
    }

    /**
     * Convert seconds to time string
     * @param int seconds
     * @param bool includeSeconds
     * @return string
     */
    public static function secondsToTimeString(int $seconds, bool $includeSeconds = false): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds / 60 % 60);
        $restSeconds = floor($seconds % 60);
        return str_pad($hours, 2, '0') . ':' . str_pad(
                $minutes,
                2,
                '0'
            ) . ($includeSeconds ? ':' . str_pad($restSeconds, 2, '0') : '');
    }

    /**
     * Convert to string
     * @return string
     */
    public function __toString(): string
    {
        return $this->getDbValue();
    }

    /**
     * Get the database value that is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the property
     * @return float
     */
    public function getDbValue(): float
    {
        return $this->seconds;
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return self::secondsToTimeString($this->seconds);
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return self::secondsToTimeString($this->seconds);
    }

    /**
     * Get a value that can be used in sort functions
     * @return int
     */
    public function getSortableValue(): int
    {
        return $this->seconds;
    }

    /**
     * Json serialize
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getRawTextString();
    }
}