<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Lang;

use function is_string;
use function strlen;

/**
 * A field to just enter time in a time format like hh:ii
 */
class Time extends Text
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 90;

    /**
     * Allow seconds to be entered
     * @var bool
     */
    public bool $allowSeconds = false;

    /**
     * Min time
     * String is timeString
     * @var string|null
     */
    public string|null $minTime = null;

    /**
     * Max time
     * String is timeString
     * @var string|null
     */
    public string|null $maxTime = null;

    /**
     * Get converted submitted value
     * @return int|null
     */
    protected function getDefaultConvertedSubmittedValue(): ?int
    {
        $value = $this->getSubmittedValue();
        if (is_string($value) && strlen($value)) {
            return \Framelix\Framelix\Time::timeStringToSeconds($value);
        }
        return null;
    }

    /**
     * Validate
     * Return error message on error or true on success
     * @return string|bool
     */
    public function validate(): string|bool
    {
        if (!$this->isVisible()) {
            return true;
        }
        $parentValidation = parent::validate();
        if ($parentValidation !== true) {
            return $parentValidation;
        }
        $value = $this->getDefaultConvertedSubmittedValue();
        if ($value) {
            if ($this->minTime !== null) {
                $limit = \Framelix\Framelix\Time::timeStringToHours($this->minTime);
                if ($value < $limit) {
                    return Lang::get(
                        '__framelix_form_validation_mintime__',
                        ['time' => \Framelix\Framelix\Time::hoursToTimeString($limit, $this->allowSeconds)]
                    );
                }
            }
            if ($this->maxTime !== null) {
                $limit = \Framelix\Framelix\Time::timeStringToHours($this->maxTime);
                if ($value > $limit) {
                    return Lang::get(
                        '__framelix_form_validation_maxtime__',
                        ['time' => \Framelix\Framelix\Time::hoursToTimeString($limit, $this->allowSeconds)]
                    );
                }
            }
        }
        return true;
    }
}