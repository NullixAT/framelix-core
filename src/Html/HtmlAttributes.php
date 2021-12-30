<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Utils\ArrayUtils;
use JsonSerializable;

use function explode;
use function implode;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Html Attributes to work nicely with the frontend
 */
class HtmlAttributes implements JsonSerializable
{
    /**
     * Internal data
     * @var array
     */
    private array $data = [
        'style' => [],
        'classes' => [],
        'other' => []
    ];

    /**
     * To string
     * Will output the HTML for the given attributes
     * @return string
     */
    public function __toString(): string
    {
        $out = [];
        if ($this->data['style']) {
            $arr = [];
            foreach ($this->data['style'] as $key => $value) {
                $arr[] = $key . ":$value;";
            }
            $out['style'] = implode(" ", $arr);
        }
        if ($this->data['classes']) {
            $out['class'] = implode(" ", $this->data['classes']);
        }
        if ($this->data['other']) {
            $out = ArrayUtils::merge($out, $this->data['other']);
        }
        $str = [];
        foreach ($out as $key => $value) {
            $str[] = $key . "=" . $this->quotify($value);
        }
        return implode(" ", $str);
    }

    /**
     * Add a class (Multiple separated with empty space)
     * @param string $className
     */
    public function addClass(string $className): void
    {
        $classes = explode(" ", $className);
        foreach ($classes as $class) {
            $class = trim($class);
            if (!$class) {
                continue;
            }
            $this->data['classes'][$class] = $class;
        }
    }

    /**
     * Remove a class (Multiple separated with empty space)
     * @param string $className
     */
    public function removeClass(string $className): void
    {
        $classes = explode(" ", $className);
        foreach ($classes as $class) {
            $class = trim($class);
            if (!$class) {
                continue;
            }
            unset($this->data['classes'][$class]);
        }
    }

    /**
     * Set a style attribute
     * @param string $key
     * @param string|null $value Null will delete the style
     */
    public function setStyle(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($this->data['style'][$key]);
            return;
        }
        $this->data['style'][$key] = $value;
    }

    /**
     * Set multiple style attributes by given array key/value
     * @param array $values
     */
    public function setStyleArray(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->setStyle($key, $value);
        }
    }

    /**
     * Get a style attribute
     * @param string $key
     * @return string|null
     */
    public function getStyle(string $key): ?string
    {
        return $this->data['style'][$key] ?? null;
    }

    /**
     * set an attribute
     * @param string $key
     * @param string|null $value Null will delete the attribute
     */
    public function set(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($this->data['other'][$key]);
            return;
        }
        $this->data['other'][$key] = $value;
    }

    /**
     * Set multiple attributes by given array key/value
     * @param array $values
     */
    public function setArray(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get an attribute
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->data['other'][$key] ?? null;
    }

    /**
     * Specify data which should be serialized to JSON
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    /**
     * Put quotes around the string, choose ' or " depending on what is not in $str
     * @param string $str
     * @return string
     */
    private function quotify(string $str): string
    {
        $singleQuote = str_contains($str, "'");
        $doubleQuote = str_contains($str, '"');
        // just remove single quotes of both double and single exist
        // this prevents HTML errors
        if ($singleQuote && $doubleQuote) {
            $str = str_replace("'", "", $str);
        }
        if ($doubleQuote) {
            return "'$str'";
        }
        return '"' . $str . '"';
    }
}