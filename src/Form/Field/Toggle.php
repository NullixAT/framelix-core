<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;

/**
 * A toggle or checkbox field
 */
class Toggle extends Field
{
    public const STYLE_TOGGLE = 'toggle';
    public const STYLE_CHECKBOX = 'checkbox';

    /**
     * The style for the toggle
     * @var string
     */
    public string $style = self::STYLE_TOGGLE;

    /**
     * Get converted submitted value
     * @return bool
     */
    protected function getDefaultConvertedSubmittedValue(): bool
    {
        $value = $this->getSubmittedValue();
        if ($value === '1' || $value === 'on') {
            return true;
        }
        return false;
    }
}