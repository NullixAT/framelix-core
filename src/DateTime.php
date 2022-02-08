<?php

namespace Framelix\Framelix;

use DateTimeZone;
use Framelix\Framelix\Db\StorablePropertyInterface;
use Framelix\Framelix\Db\StorableSchemaProperty;

use function ceil;
use function date;
use function gmdate;
use function is_int;
use function is_string;
use function preg_replace;
use function strtotime;
use function trim;

/**
 * Framelix datetime
 */
class DateTime extends \DateTime implements StorablePropertyInterface
{
    /**
     * Convert any value to given date format
     * @param mixed $value
     * @param string $format
     * @return string|null Null if value cannot be converted
     */
    public static function anyToFormat(mixed $value, string $format = "d.m.Y"): ?string
    {
        $value = self::anyToUnixtime($value);
        if ($value === false) {
            return null;
        }
        return date($format, $value);
    }

    /**
     * Convert any value to unixtime
     * @param mixed $value
     * @return false|int False if cannot be converted to unixtime
     */
    public static function anyToUnixtime(mixed $value): false|int
    {
        if ($value instanceof \Framelix\Framelix\Date) {
            return $value->dateTime->getTimestamp();
        }
        if ($value instanceof self) {
            return $value->getTimestamp();
        }
        if ($value === null) {
            return false;
        }
        if (!is_string($value) && !is_int($value)) {
            return false;
        }
        if (is_int($value)) {
            return $value;
        }
        if (trim($value) === '') {
            return false;
        }
        // sanitize a bit
        $value = preg_replace("~[^a-z0-9/.:\- +]~i", "", $value);
        return strtotime($value);
    }

    /**
     * Setup the property database schema definition to this storable property itself
     * This defines how the column will be created in the database
     * @param StorableSchemaProperty $storableSchemaProperty
     */
    public static function setupSelfStorableSchemaProperty(StorableSchemaProperty $storableSchemaProperty): void
    {
        $storableSchemaProperty->databaseType = "datetime";
    }

    /**
     * Create an instance from the original database value
     * @param mixed $dbValue
     * @return self|null
     */
    public static function createFromDbValue(mixed $dbValue): ?self
    {
        $phpValue = self::create($dbValue . " UTC");
        $phpValue?->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $phpValue;
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
     * Create a new instance from any given value
     * int is considered a unix timestamp
     * @param mixed $value
     * @return DateTime|null
     */
    public static function create(mixed $value): ?DateTime
    {
        if ($value instanceof self) {
            return clone $value;
        }
        if ($value instanceof \Framelix\Framelix\Date) {
            return clone $value->dateTime;
        }
        $unixtime = self::anyToUnixtime($value);
        if (!$unixtime) {
            return null;
        }
        $instance = new self();
        $instance->setTimestamp($unixtime);
        return $instance;
    }

    /**
     * Get max date of an array of datetimes
     * @param mixed ...$dateTimes
     * @return DateTime|null
     */
    public static function max(mixed ...$dateTimes): ?DateTime
    {
        $max = null;
        foreach ($dateTimes as $dateTime) {
            $dateTime = $dateTime instanceof DateTime ? $dateTime : self::create($dateTime);
            if ($dateTime && ($max === null || $max < $dateTime)) {
                $max = $dateTime;
            }
        }
        return $max;
    }

    /**
     * Get min date of an array of datetimes
     * @param mixed ...$dateTimes
     * @return DateTime|null
     */
    public static function min(mixed ...$dateTimes): ?DateTime
    {
        $min = null;
        foreach ($dateTimes as $dateTime) {
            $dateTime = $dateTime instanceof DateTime ? $dateTime : self::create($dateTime);
            if ($dateTime && ($min === null || $min > $dateTime)) {
                $min = $dateTime;
            }
        }
        return $min;
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
     * Get start months  first day of current quarter
     * @return self 1,4,7,10
     */
    public function getQuarterStartMonth(): self
    {
        return self::create($this)->setDate($this->getYear(), ($this->getQuarter() * 3) - 2, 1);
    }

    /**
     * Get end months first day of current quarter
     * @return self 1,4,7,10
     */
    public function getQuarterEndMonth(): self
    {
        return self::create($this)->setDate($this->getYear(), $this->getQuarter() * 3, 1);
    }

    /**
     * Get quarter of year
     * @return int 1,2,3,4
     */
    public function getQuarter(): int
    {
        return (int)ceil($this->getMonth() / 3);
    }

    /**
     * Get year
     * @return int
     */
    public function getYear(): int
    {
        return (int)$this->format("Y");
    }

    /**
     * Get month
     * @return int
     */
    public function getMonth(): int
    {
        return (int)$this->format("n");
    }

    /**
     * Get translated month name and year
     * @param bool $shortName
     * @param string|null $lang
     * @return string
     */
    public function getMonthNameAndYear(bool $shortName = false, ?string $lang = null): string
    {
        return $this->getMonthName($shortName, $lang) . " " . $this->getYear();
    }

    /**
     * Get translated month name
     * @param bool $shortName
     * @param string|null $lang
     * @return string
     */
    public function getMonthName(bool $shortName = false, ?string $lang = null): string
    {
        return Lang::get(
            '__framelix_month' . ($shortName ? 'short' : '') . '_' . $this->getMonth() . '__',
            null,
            $lang
        );
    }

    /**
     * Get day of month
     * @return int
     */
    public function getDayOfMonth(): int
    {
        return (int)$this->format("d");
    }

    /**
     * Get day of week, monday is 1, sunday is 7
     * @return int
     */
    public function getDayOfWeek(): int
    {
        return (int)$this->format("N");
    }

    /**
     * Get translated day of week name
     * @param bool $shortName
     * @param string|null $lang
     * @return string
     */
    public function getDayName(bool $shortName = false, ?string $lang = null): string
    {
        return Lang::get(
            '__framelix_day' . ($shortName ? 'short' : '') . '_' . $this->getDayOfWeek() . '__',
            null,
            $lang
        );
    }

    /**
     * Get hours part of time in 24h
     * @return int
     */
    public function getHours(): int
    {
        return (int)$this->format("H");
    }

    /**
     * Get minutes  part of time
     * @return int
     */
    public function getMinutes(): int
    {
        return (int)$this->format("i");
    }

    /**
     * Get seconds part of time
     * @return int
     */
    public function getSeconds(): int
    {
        return (int)$this->format("s");
    }

    /**
     * Set year
     * @param int $year
     * @return static
     */
    public function setYear(int $year): static
    {
        $this->setDate($year, $this->getMonth(), $this->getDayOfMonth());
        return $this;
    }

    /**
     * Set month
     * @param int $month
     * @return static
     */
    public function setMonth(int $month): static
    {
        $this->setDate($this->getYear(), $month, $this->getDayOfMonth());
        return $this;
    }

    /**
     * Set day of month
     * Use -1 to set last day of this month
     * @param int $day
     * @return static
     */
    public function setDayOfMonth(int $day): static
    {
        $this->setDate($this->getYear(), $this->getMonth(), $day === -1 ? (int)$this->format("t") : $day);
        return $this;
    }

    /**
     * Set hours
     * @param int $hours
     * @return static
     */
    public function setHours(int $hours): static
    {
        $this->setTime($hours, $this->getMinutes(), $this->getSeconds());
        return $this;
    }

    /**
     * Set minutes
     * @param int $minutes
     * @return static
     */
    public function setMinutes(int $minutes): static
    {
        $this->setTime($this->getHours(), $minutes, $this->getSeconds());
        return $this;
    }

    /**
     * Set seconds
     * @param int $seconds
     * @return static
     */
    public function setSeconds(int $seconds): static
    {
        $this->setTime($this->getHours(), $this->getMinutes(), $seconds);
        return $this;
    }

    /**
     * Get x seconds/minutes/hours difference to the given date
     * It returns the highest unit when it has reached 1 and round up to next integer
     * So if diff is 30 seconds, it returns 30 seconds
     * So if diff is 62 seconds, it returns 2 minute
     * So if diff is 3602 seconds, it returns 2 hour
     * @param DateTime $otherTime
     * @param string|null $lang
     * @return string
     */
    public function getRelativeTimeUnits(DateTime $otherTime, ?string $lang = null): string
    {
        $diff = abs($this->getTimestamp() - $otherTime->getTimestamp());
        if ($diff >= 3600) {
            return Lang::get("__framelix_time_hours__", [ceil($diff / 3600)], $lang);
        } elseif ($diff >= 60) {
            return Lang::get("__framelix_time_minutes__", [ceil($diff / 60)], $lang);
        } else {
            return Lang::get("__framelix_time_seconds__", [$diff], $lang);
        }
    }

    /**
     * Get the database value that is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the property
     * @return string
     */
    public function getDbValue(): string
    {
        return gmdate("Y-m-d H:i:s", $this->getTimestamp());
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return $this->format("d.m.Y H:i:s");
    }

    /**
     * Get a value that is explicitely used when displayed inside a html table
     * @return string
     */
    public function getHtmlTableValue(): string
    {
        return $this->getHtmlString();
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this->format("d.m.Y H:i:s");
    }

    /**
     * Get a value that can be used in sort functions
     * @return int
     */
    public function getSortableValue(): int
    {
        return (int)$this->format("YmdHis");
    }

    /**
     * Clone self and return the clone
     * @return $this
     */
    public function clone(): self
    {
        return clone $this;
    }

    /**
     * Clone self and return a Date instance
     * @return Date
     */
    public function cloneToDate(): Date
    {
        return Date::create($this);
    }

    /**
     * Json serialize
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getDbValue();
    }
}