<?php

namespace Framelix\Framelix\Utils;

use function array_merge;
use function array_pop;
use function array_shift;
use function explode;
use function max;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 * Php Doc Parser
 * Split a phpdoc into it's annotations and description
 */
class PhpDocParser
{

    /**
     * Parse all annotations that describe a variable (param, property, etc...)
     * @param string $phpDocComment
     * @param string $annotationType
     * @return array ["variableName" => ["name" => "parameterName", "type" => "parameterType", "description" => string[]]]
     */
    public static function parseVariableDescriptions(string $phpDocComment, string $annotationType = 'param'): array
    {
        $annotations = self::parse($phpDocComment);
        $arr = [];
        foreach ($annotations['annotations'] as $row) {
            if ($row['type'] === $annotationType) {
                $line = $row['value'][0];
                preg_match("~^[\s]*([^\s]+)\s*\\\$([^\s]+)(.*)~", $line, $matchWithType);
                preg_match("~^[\s]*\\\$([^\s]+)(.*)~", $line, $matchWithoutType);
                if ($matchWithType) {
                    $name = $matchWithType[2];
                    $type = $matchWithType[1];
                    $value = $row['value'];
                    array_shift($value);
                    if ($matchWithType[3]) {
                        $lines = array_merge([$matchWithType[3]], $value);
                    } else {
                        $lines = $value;
                    }
                    $arr[$name] = ['name' => $name, 'type' => $type, 'description' => $lines];
                } elseif ($matchWithoutType) {
                    $name = $matchWithoutType[1];
                    $type = null;
                    $value = $row['value'];
                    array_shift($value);
                    if ($matchWithoutType[2]) {
                        $lines = array_merge([$matchWithoutType[2]], $value);
                    } else {
                        $lines = $value;
                    }
                    $arr[$name] = ['name' => $name, 'type' => $type, 'description' => $lines];
                }
            }
        }
        return $arr;
    }

    /**
     * Parse doc comment into array
     * @param string $phpDocComment
     * @return array ['description' => [string[]], 'annotations' => ['type' => 'AnnotationType', 'value' => string[]]]
     */
    public static function parse(string $phpDocComment): array
    {
        $arr = ['description' => [], 'annotations' => []];
        $docCommentLines = explode("\n", $phpDocComment);
        array_shift($docCommentLines);
        array_pop($docCommentLines);
        $annotationKey = -1;
        $annotationType = null;
        foreach ($docCommentLines as $line) {
            $line = substr(trim($line), 2);
            $line = str_replace("*\/", "*/", $line);
            if (str_starts_with($line, "@")) {
                $annotationKey++;
                $pos = strpos($line, " ");
                if ($pos === false) {
                    $pos = null;
                }
                $annotationType = strtolower(substr($line, 1, max($pos - 1, 0)));
                if ($pos !== null) {
                    $line = substr($line, $pos + 1);
                }
            }
            if ($annotationType === null) {
                $arr['description'][] = $line;
            } else {
                if (!isset($arr['annotations'][$annotationKey])) {
                    $arr['annotations'][$annotationKey] = [
                        'type' => $annotationType,
                        'value' => []
                    ];
                }
                $arr['annotations'][$annotationKey]['value'][] = $line;
            }
        }
        return $arr;
    }
}