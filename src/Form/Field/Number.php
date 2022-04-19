<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Lang;
use Framelix\Framelix\Utils\NumberUtils;
use function is_string;

/**
 * A field to enter numbers only
 */
class Number extends Text
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 150;

    /**
     * Comma separator
     * @var string
     */
    public string $commaSeparator = ",";

    /**
     * Thousend separator
     * @var string
     */
    public string $thousandSeparator = ".";

    /**
     * Decimals
     * @var int
     */
    public int $decimals = 0;

    /**
     * Min for submitted value
     * @var float|null
     */
    public ?float $min = null;

    /**
     * Max for submitted value
     * @var float|null
     */
    public ?float $max = null;

    /**
     * Get converted submitted value
     * @return float|int|null
     */
    protected function getDefaultConvertedSubmittedValue(): float|int|null
    {
        $value = $this->getSubmittedValue();
        if (is_string($value)) {
            $value = NumberUtils::toFloat($value, $this->decimals, $this->commaSeparator);
            if ($this->decimals === 0) {
                $value = (int)$value;
            }
            return $value;
        }
        return null;
    }

    /**
     * Set format to integer only without thousand separator
     * @return void
     */
    public function setIntegerOnly(): void
    {
        $this->decimals = 0;
        $this->thousandSeparator = "";
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
        if ($this->min !== null && $value < $this->min) {
            return Lang::get(
                '__framelix_form_validation_min__',
                ['number' => NumberUtils::format($this->min, $this->decimals)]
            );
        }
        if ($this->max !== null && $value > $this->max) {
            return Lang::get(
                '__framelix_form_validation_max__',
                ['number' => NumberUtils::format($this->max, $this->decimals)]
            );
        }
        return true;
    }
}