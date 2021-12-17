<?php

namespace Framelix\Framelix\Form;

use Exception;
use JsonSerializable;

use function in_array;

/**
 * A fields visibility condition
 */
class FieldVisibilityCondition implements JsonSerializable
{
    /**
     * The condition data
     * @var array
     */
    public array $data = [];

    /**
     * Add an && condition
     * @return static
     */
    public function and(): static
    {
        $this->data[] = [
            'type' => 'and'
        ];
        return $this;
    }

    /**
     * Add an || condition
     * @return static
     */
    public function or(): static
    {
        $this->data[] = [
            'type' => 'or'
        ];
        return $this;
    }

    /**
     * Does the fields value is empty
     * @param string $fieldName
     * @return static
     */
    public function empty(string $fieldName): static
    {
        $this->data[] = [
            'type' => 'empty',
            'field' => $fieldName
        ];
        return $this;
    }

    /**
     * Does the fields value is not empty
     * @param string $fieldName
     * @return static
     */
    public function notEmpty(string $fieldName): static
    {
        $this->data[] = [
            'type' => 'notEmpty',
            'field' => $fieldName
        ];
        return $this;
    }

    /**
     * Does the fields value contains a given value
     * If fieldValue is array than one of the value must match
     * @param string $fieldName
     * @param mixed $value
     * @return static
     */
    public function like(string $fieldName, mixed $value): static
    {
        $this->data[] = [
            'type' => 'like',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Does the fields value not contains a given value
     * If fieldValue is array than all of the value must not match
     * @param string $fieldName
     * @param mixed $value
     * @return static
     */
    public function notLike(string $fieldName, mixed $value): static
    {
        $this->data[] = [
            'type' => 'notLike',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Does the fields value equal given value
     * If fieldValue is array than one of the value must match
     * @param string $fieldName
     * @param mixed $value
     * @return static
     */
    public function equal(string $fieldName, mixed $value): static
    {
        $this->data[] = [
            'type' => 'equal',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Does the fields value not equal given value
     * If fieldValue is array than all of the value must not match
     * @param string $fieldName
     * @param mixed $value
     * @return static
     */
    public function notEqual(string $fieldName, mixed $value): static
    {
        $this->data[] = [
            'type' => 'notEqual',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Does the fields value is greather than
     * If fieldValue is array than it counts the elements in the field value
     * @param string $fieldName
     * @param float|int $value
     * @return static
     */
    public function greatherThan(string $fieldName, float|int $value): static
    {
        $this->data[] = [
            'type' => 'greatherThan',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Does the fields value is greather than equal
     * If fieldValue is array than it counts the elements in the field value
     * @param string $fieldName
     * @param float|int $value
     * @return static
     */
    public function greatherThanEqual(string $fieldName, float|int $value): static
    {
        $this->data[] = [
            'type' => 'greatherThanEqual',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Does the fields value is lower than
     * If fieldValue is array than it counts the elements in the field value
     * @param string $fieldName
     * @param float|int $value
     * @return static
     */
    public function lowerThan(string $fieldName, float|int $value): static
    {
        $this->data[] = [
            'type' => 'lowerThan',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Does the fields value is lower than equal
     * If fieldValue is array than it counts the elements in the field value
     * @param string $fieldName
     * @param float|int $value
     * @return static
     */
    public function lowerThanEqual(string $fieldName, float|int $value): static
    {
        $this->data[] = [
            'type' => 'lowerThanEqual',
            'field' => $fieldName,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $this->validateData();
        $properties = [];
        foreach ($this as $key => $value) {
            $properties[$key] = $value;
        }
        return [
            'properties' => $properties
        ];
    }

    /**
     * Validate data
     */
    private function validateData(): void
    {
        for ($i = 0; $i < count($this->data); $i++) {
            if ($i > 0 && $i % 2 !== 0 && !in_array($this->data[$i]['type'], ['and', 'or'])) {
                throw new Exception(
                    __CLASS__ . " -> You need an and/or compare operation after an " . ($this->data[$i - 1]['type']) . " condition"
                );
            }
        }
    }
}