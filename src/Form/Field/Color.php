<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;

use function is_string;
use function strlen;
use function strtoupper;

/**
 * A color field with a color picker
 */
class Color extends Field
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 130;

    /**
     * Get submitted value
     * @return string|null
     */
    public function getSubmittedValue(): ?string
    {
        $value = parent::getSubmittedValue();
        if (is_string($value) && strlen($value) === 7) {
            return strtoupper($value);
        }
        return null;
    }
}