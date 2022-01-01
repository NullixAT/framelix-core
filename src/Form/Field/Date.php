<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Lang;

/**
 * A date field
 */
class Date extends Text
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 150;

    /**
     * Min date for submitted value
     * @var \Framelix\Framelix\Date|null
     */
    public \Framelix\Framelix\Date|null $minDate = null;

    /**
     * Max date for submitted value
     * @var \Framelix\Framelix\Date|null
     */
    public \Framelix\Framelix\Date|null $maxDate = null;

    /**
     * Get converted submitted value
     * @return \Framelix\Framelix\Date|null
     */
    protected function getDefaultConvertedSubmittedValue(): ?\Framelix\Framelix\Date
    {
        return \Framelix\Framelix\Date::create($this->getSubmittedValue());
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
        $value = $this->getConvertedSubmittedValue();
        if ($value && $this->minDate !== null && \Framelix\Framelix\Date::compare($value, $this->minDate) === "<") {
            return Lang::get('__framelix_form_validation_mindate__', ['date' => DateTime::anyToFormat($this->minDate)]);
        }
        if ($value && $this->maxDate !== null && \Framelix\Framelix\Date::compare($value, $this->maxDate) === ">") {
            return Lang::get('__framelix_form_validation_maxdate__', ['date' => DateTime::anyToFormat($this->minDate)]);
        }
        return true;
    }
}