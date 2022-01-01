<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Lang;

/**
 * A datetime field
 */
class DateTime extends Text
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 200;

    /**
     * Min date for submitted value
     * @var \Framelix\Framelix\DateTime|null
     */
    public \Framelix\Framelix\DateTime|null $minDateTime = null;

    /**
     * Max date for submitted value
     * @var \Framelix\Framelix\DateTime|null
     */
    public \Framelix\Framelix\DateTime|null $maxDateTime = null;

    /**
     * Allow seconds
     * @var bool
     */
    public bool $allowSeconds = false;

    /**
     * Get converted submitted value
     * @return \Framelix\Framelix\DateTime|null
     */
    protected function getDefaultConvertedSubmittedValue(): ?\Framelix\Framelix\DateTime
    {
        return \Framelix\Framelix\DateTime::create($this->getSubmittedValue());
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
            $valueMin = clone $value;
            $valueMin->setSeconds(0);
            $valueMax = clone $value;
            $valueMax->setSeconds(59);
            if ($this->minDateTime !== null && $valueMin < $this->minDateTime) {
                return Lang::get(
                    '__framelix_form_validation_mindate__',
                    ['date' => $this->minDateTime->getRawTextString()]
                );
            }
            if ($this->maxDateTime !== null & $valueMax > $this->maxDateTime) {
                return Lang::get(
                    '__framelix_form_validation_maxdate__',
                    ['date' => $this->maxDateTime->getRawTextString()]
                );
            }
        }
        return true;
    }
}