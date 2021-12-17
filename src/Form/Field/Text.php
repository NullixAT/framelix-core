<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Lang;

use function is_string;
use function strlen;

/**
 * The most basic input field
 * Is the base class for other fields as well
 */
class Text extends Field
{
    /**
     * Placeholder
     * @var string|null
     */
    public ?string $placeholder = null;

    /**
     * Spellcheck
     * @var bool
     */
    public bool $spellcheck = false;

    /**
     * Min length for submitted value
     * @var int|null
     */
    public ?int $minLength = null;

    /**
     * Max length for submitted value
     * @var int|null
     */
    public ?int $maxLength = null;

    /**
     * Type for this input field
     * @var string
     */
    public string $type = "text";

    /**
     * A list of strings for autocomplete suggestion
     * @var string[]|null
     */
    public ?array $autocompleteSuggestions = null;

    /**
     * Get submitted value
     * @return string|null
     */
    public function getSubmittedValue(): ?string
    {
        $value = parent::getSubmittedValue();
        if (is_string($value)) {
            return $value;
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
        $value = $this->getSubmittedValue();
        $count = is_string($value) ? strlen($value) : 0;
        if ($this->minLength !== null && $count < $this->minLength) {
            return Lang::get('__framelix_form_validation_minlength__', ['number' => $this->minLength]);
        }
        if ($this->maxLength !== null && $count > $this->maxLength) {
            return Lang::get('__framelix_form_validation_maxlength__', ['number' => $this->maxLength]);
        }
        return true;
    }
}