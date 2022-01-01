<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Lang;
use Framelix\Framelix\ObjectTransformable;
use Framelix\Framelix\Storable\Storable;

use function count;
use function is_array;
use function is_string;

/**
 * A select field to provide custom ways to have single/multiple select options
 */
class Select extends Field
{

    /**
     * Show options in a dropdown
     * If false then all options are instantly visible
     * @var bool
     */
    public bool $dropdown = true;

    /**
     * Is multiple
     * @var bool
     */
    public bool $multiple = false;

    /**
     * Does contain a search input for filter existing options
     * @var bool
     */
    public bool $searchable = false;

    /**
     * Min selected items for submitted value
     * @var int|null
     */
    public ?int $minSelectedItems = null;

    /**
     * Max selected items for submitted value
     * @var int|null
     */
    public ?int $maxSelectedItems = null;

    /**
     * The label when no option has been selected in single selects
     * @var  string
     */
    public string $chooseOptionLabel = '__framelix_form_select_chooseoption_label__';

    /**
     * The label when no option has been selected in single selects
     * @var  string
     */
    public string $noOptionsLabel = '__framelix_form_select_noptions_label__';

    /**
     * All available options
     * @var array
     */
    protected array $options = [];

    /**
     * Add options by using given storables as key/label
     * @param Storable[]|null $storables
     */
    public function addOptionsByStorables(?array $storables): void
    {
        if (is_array($storables)) {
            foreach ($storables as $storable) {
                if ($storable instanceof Storable) {
                    $this->addOption((string)$storable->id, $storable->getHtmlString());
                }
            }
        }
    }

    /**
     * Add multiple options
     * @param array $options
     */
    public function addOptions(array $options): void
    {
        foreach ($options as $value => $label) {
            $this->addOption($value, $label);
        }
    }

    /**
     * Add an option
     * @param string|int $value
     * @param mixed $label
     */
    public function addOption(string|int $value, mixed $label): void
    {
        $optionKey = $this->indexOfOptionValue($value);
        if ($optionKey === -1) {
            if ($label instanceof ObjectTransformable) {
                $label = $label->getHtmlString();
            }
            $this->options[] = [(string)$value, $label];
        }
    }

    /**
     * Remove an option
     * @param string $value
     */
    public function removeOption(string $value): void
    {
        $optionKey = $this->indexOfOptionValue($value);
        unset($this->options[$optionKey]);
    }

    /**
     * Get options
     * @return string[][]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get option label to given value
     * @param string $value
     * @return mixed
     */
    public function getOptionLabel(string $value): mixed
    {
        $optionKey = $this->indexOfOptionValue($value);
        if ($optionKey === -1) {
            return null;
        }
        return $this->options[$optionKey][1] ?? null;
    }

    /**
     * Remove multiple option values
     * @param array $values
     */
    public function removeOptions(array $values): void
    {
        foreach ($values as $value) {
            $this->removeOption($value);
        }
    }

    /**
     * Get option array index for given value
     * @param string $value
     * @return int -1 If not found
     */
    public function indexOfOptionValue(string $value): int
    {
        foreach ($this->options as $k => $row) {
            if ($row[0] === $value) {
                return $k;
            }
        }
        return -1;
    }

    /**
     * Get submitted value
     * @return string|array|null
     */
    public function getSubmittedValue(): string|array|null
    {
        $value = parent::getSubmittedValue();
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            $newArr = [];
            foreach ($value as $k => $v) {
                if (is_string($v)) {
                    $newArr[$k] = $v;
                }
            }
            return $newArr ?: null;
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
        $count = is_array($value) ? count($value) : 0;
        if ($this->minSelectedItems !== null && $count < $this->minSelectedItems) {
            return Lang::get('__framelix_form_validation_minselecteditems__', ['number' => $this->minSelectedItems]);
        }
        if ($this->maxSelectedItems !== null && $count > $this->maxSelectedItems) {
            return Lang::get('__framelix_form_validation_maxselecteditems__', ['number' => $this->maxSelectedItems]);
        }
        return true;
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['properties']['options'] = $this->options;
        return $data;
    }


}