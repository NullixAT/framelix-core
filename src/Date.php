<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\StorablePropertyInterface;
use Framelix\Framelix\Db\StorableSchemaProperty;

use function date_diff;

/**
 * Date wrapper for database property
 * Just contains a real datetime that will be converted to date in database, instead of datetime
 */
class Date implements StorablePropertyInterface
{
    /**
     * Datetime object
     * @var DateTime
     */
    public DateTime $dateTime;

    /**
     * Setup the property database schema definition to this storable property itself
     * This defines how the column will be created in the database
     * @param StorableSchemaProperty $storableSchemaProperty
     */
    public static function setupSelfStorableSchemaProperty(StorableSchemaProperty $storableSchemaProperty): void
    {
        $storableSchemaProperty->databaseType = "date";
    }

    /**
     * Create an instance from the original database value
     * @param mixed $dbValue
     * @return self|null
     */
    public static function createFromDbValue(mixed $dbValue): ?self
    {
        return self::create($dbValue);
    }

    /**
     * Create an instance from a submitted form value
     * @param mixed $formValue
     * @return self|null
     */
    public static function createFromFormValue(mixed $formValue): ?self
    {
        return self::create($formValue);
    }

    /**
     * Create an instance from a value
     * @param mixed $value
     * @return self|null
     */
    public static function create(mixed $value): ?self
    {
        $dateTime = DateTime::create($value);
        if (!$dateTime) {
            return null;
        }
        $instance = new self();
        $instance->dateTime = $dateTime;
        return $instance;
    }

    /**
     * Compare given dates
     * @param mixed $dateA
     * @param mixed $dateB
     * @return string Return = for equal, < for A as lower than B, > for A as greather than B
     */
    public static function compare(mixed $dateA, mixed $dateB): string
    {
        $dateA = self::create($dateA)?->getSortableValue() ?? 0;
        $dateB = self::create($dateB)?->getSortableValue() ?? 0;
        if ($dateA < $dateB) {
            return "<";
        }
        if ($dateA > $dateB) {
            return ">";
        }
        return "=";
    }

    /**
     * Get max date of an array of datetimes
     * @param mixed ...$dates
     * @return Date|null
     */
    public static function max(mixed ...$dates): ?Date
    {
        $max = null;
        foreach ($dates as $date) {
            $date = $date instanceof Date ? $date : self::create($date);
            if ($date && ($max === null || $max->dateTime < $date->dateTime)) {
                $max = $date;
            }
        }
        return $max;
    }

    /**
     * Get min date of an array of dates
     * @param mixed ...$dates
     * @return Date|null
     */
    public static function min(mixed ...$dates): ?Date
    {
        $min = null;
        foreach ($dates as $date) {
            $date = $date instanceof Date ? $date : self::create($date);
            if ($date && ($min === null || $min->dateTime > $date->dateTime)) {
                $min = $date;
            }
        }
        return $min;
    }

    /**
     * Get a range of days between min and max
     * @param mixed $minDate Min date, including this
     * @param mixed $maxDate Max date, including this
     * @return self[]
     */
    public static function rangeDays(mixed $minDate, mixed $maxDate): array
    {
        $arr = [];
        $minDate = self::create($minDate);
        $maxDate = self::create($maxDate);
        if (!$minDate || !$maxDate) {
            return $arr;
        }
        $min = self::min($minDate, $maxDate);
        $max = self::max($minDate, $maxDate);
        $diff = date_diff($min->dateTime, $max->dateTime);
        for ($i = 0; $i <= $diff->days; $i++) {
            $clone = $min->clone();
            $arr[] = $clone;
            $min->dateTime->modify("+ 1 day");
        }
        return $arr;
    }

    /**
     * Get a range of months between min and max
     * Each month is starting with the first day of month
     * @param mixed $minDate Min date, including this
     * @param mixed $maxDate Max date, including this
     * @return self[]
     */
    public static function rangeMonth(mixed $minDate, mixed $maxDate): array
    {
        $arr = [];
        $minDate = self::create($minDate);
        $maxDate = self::create($maxDate);
        if (!$minDate || !$maxDate) {
            return $arr;
        }
        $min = self::min($minDate, $maxDate);
        $max = self::max($minDate, $maxDate);
        $min->dateTime->setDayOfMonth(1);
        $max->dateTime->setDayOfMonth(-1);
        while ((int)$max->dateTime->format("Ym") >= (int)$min->dateTime->format("Ym")) {
            $clone = $min->clone();
            $arr[] = $clone;
            $min->dateTime->modify("+ 1 month");
        }
        return $arr;
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
     * @return string
     */
    public function getDbValue(): string
    {
        return $this->dateTime->format("Y-m-d");
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return $this->dateTime->format("d.m.Y");
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this->dateTime->format("d.m.Y");
    }

    /**
     * Get a value that can be used in sort functions
     * @return int
     */
    public function getSortableValue(): int
    {
        return (int)$this->dateTime->format("Ymd");
    }

    /**
     * Json serialize
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getDbValue();
    }

    /**
     * Clone self and return the clone
     * @return self
     */
    public function clone(): self
    {
        $clone = new self();
        $clone->dateTime = clone $this->dateTime;
        return $clone;
    }

    /**
     * Clone self and return a DateTime instance
     * @return DateTime
     */
    public function cloneToDateTime(): DateTime
    {
        return DateTime::create($this);
    }

    /**
     * On clone
     */
    public function __clone(): void
    {
        throw new Exception(
            'Native clone isnt supported - Use ->clone() on the storable',
            ErrorCode::NATIVE_CLONE
        );
    }
}