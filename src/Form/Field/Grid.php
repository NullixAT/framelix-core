<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Utils\ArrayUtils;

use function array_keys;
use function count;
use function is_array;
use function str_ends_with;
use function substr;

/**
 * A grid to dynamically add rows with columns of several field instances
 */
class Grid extends Field
{
    /**
     * The fields in the grid
     * @var Field[]
     */
    public array $fields = [];

    /**
     * Grid table take full width when no max width is given
     * @var bool
     */
    public bool $fullWidth = false;

    /**
     * Can a row be deleted
     * @var bool
     */
    public bool $deletable = true;

    /**
     * Can a row be added
     * @var bool
     */
    public bool $addable = true;

    /**
     * Min rows for submitted value
     * @var int|null
     */
    public ?int $minRows = null;

    /**
     * Max rows for submitted value
     * @var int|null
     */
    public ?int $maxRows = null;

    /**
     * Add field
     * @param Field $field
     */
    public function addField(Field $field): void
    {
        if ($field instanceof self) {
            throw new Exception("Cannot put a Grid field into a Grid field", ErrorCode::FORM_GRID_NESTED_NOT_ALLOWED);
        }
        if ($field->label === null
            && $this->label !== null
            && str_starts_with($this->label, "__")
            && str_ends_with($this->label, "__")) {
            $field->label = substr($this->label, -2) . "_" . $field->name . "__";
        }
        $this->fields[$field->name] = $field;
    }

    /**
     * Remove field
     * @param string $fieldName
     */
    public function removeField(string $fieldName): void
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * Get default converted submitted value
     * @return array|null
     */
    public function getDefaultConvertedSubmittedValue(): ?array
    {
        $value = parent::getSubmittedValue();
        if (!is_array($value)) {
            return null;
        }
        $arr = [];
        if (isset($value['rows']) && is_array($value['rows'])) {
            foreach ($value['rows'] as $key => $row) {
                foreach ($this->fields as $fieldName => $field) {
                    $field->name = $this->name . "[rows][$key][$fieldName]";
                    $submittedValue = $field->getConvertedSubmittedValue();
                    $field->name = $fieldName;
                    $arr[$key][$fieldName] = $submittedValue;
                }
            }
        }
        return $arr ?: null;
    }

    /**
     * Get submitted value
     * This get the values that are visible in the mask
     * If yoo want deleted keys, use getSubmittedDeletedKeys
     * @return array|null
     */
    public function getSubmittedValue(): array|null
    {
        $value = parent::getSubmittedValue();
        if (!is_array($value)) {
            return null;
        }
        $arr = [];
        if (isset($value['rows']) && is_array($value['rows'])) {
            foreach ($value['rows'] as $key => $row) {
                foreach ($this->fields as $fieldName => $field) {
                    $field->name = $this->name . "[rows][$key][$fieldName]";
                    $submittedValue = $field->getSubmittedValue();
                    $field->name = $fieldName;
                    $arr[$key][$fieldName] = $submittedValue;
                }
            }
        }
        return $arr ?: null;
    }

    /**
     * Get submitted deleted keys
     * This are the keys from the rows that the user has deleted
     * @return array|null
     */
    public function getSubmittedDeletedKeys(): array|null
    {
        $value = parent::getSubmittedValue();
        if (!is_array($value)) {
            return null;
        }
        if (isset($value['deleted']) && is_array($value['deleted'])) {
            return array_keys($value['deleted']);
        }
        return null;
    }

    /**
     * Validate
     * Return error message on error or true on success
     * @return string|array|bool
     */
    public function validate(): string|array|bool
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
        if ($this->minRows !== null && $count < $this->minRows) {
            return Lang::get('__framelix_form_validation_mingridrows__', ['number' => $this->minRows]);
        }
        if ($this->maxRows !== null && $count > $this->maxRows) {
            return Lang::get('__framelix_form_validation_maxgridrows__', ['number' => $this->maxRows]);
        }
        // validate each single row
        if ($count) {
            $arr = [];
            foreach ($value as $rowKey => $row) {
                foreach ($this->fields as $fieldName => $field) {
                    $field->name = $this->name . "[rows][$rowKey][$fieldName]";
                    $validation = $field->validate();
                    $field->name = $fieldName;
                    if ($validation !== true) {
                        $arr[$rowKey][$fieldName] = $validation;
                    }
                }
            }
            return $arr ?: true;
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
        if (is_array($this->defaultValue)) {
            $arr = [];
            foreach ($this->defaultValue as $key => $row) {
                foreach ($this->fields as $field) {
                    $arr[$key][$field->name] = ArrayUtils::getValue($row, $field->name);
                }
            }
            $data['properties']['defaultValue'] = $arr;
        }
        return $data;
    }
}