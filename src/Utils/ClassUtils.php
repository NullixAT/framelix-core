<?php

namespace Framelix\Framelix\Utils;

use Exception;

use function class_exists;
use function file_exists;
use function get_class;
use function is_object;
use function is_string;
use function preg_replace;
use function realpath;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Class utilities for frequent tasks
 */
class ClassUtils
{

    /**
     * Get the basename (without namespace) of a class absolute class name
     * @param object|string $absoluteClassName
     * @return string
     */
    public static function getClassBaseName(object|string $absoluteClassName): string
    {
        $absoluteClassName = is_object($absoluteClassName) ? get_class($absoluteClassName) : $absoluteClassName;
        return substr($absoluteClassName, strrpos($absoluteClassName, "\\") + 1);
    }

    /**
     * Get a html class the given php class
     * @param object|string $phpClassName
     * @param string|null $append Append the given key
     * @return string
     */
    public static function getHtmlClass(object|string $phpClassName, ?string $append = null): string
    {
        $phpClassName = is_object($phpClassName) ? get_class($phpClassName) : $phpClassName;
        if (str_starts_with($phpClassName, "Framelix\\")) {
            $phpClassName = substr($phpClassName, 9);
        }
        return strtolower(StringUtils::slugify($phpClassName)) . ($append ? "-" . strtolower($append) : null);
    }

    /**
     * Get a lang key for the given class
     * @param object|string $phpClassName
     * @param string|null $appendKey Append the given key
     * @return string
     */
    public static function getLangKey(object|string $phpClassName, ?string $appendKey = null): string
    {
        $phpClassName = is_object($phpClassName) ? get_class($phpClassName) : $phpClassName;
        if (str_starts_with($phpClassName, "Framelix\\")) {
            $phpClassName = substr($phpClassName, 9);
        }
        return strtolower(
            "__" . str_replace(
                "\\",
                "_",
                $phpClassName
            ) . (is_string($appendKey) ? "_" . $appendKey : '') . "__"
        );
    }

    /**
     * Validate given class name if it is a correct classname
     * It does not check if the class exists
     * This should always be used when className is provided by user modifiable strings (callPhpMethod, etc...)
     * @param mixed $className
     * @throws Exception If not valid
     */
    public static function validateClassName(mixed $className): void
    {
        $classNameSanitized = preg_replace("~[^\\\\a-z0-9_]~i", "", (string)$className);
        if (!$classNameSanitized || $classNameSanitized !== $className || !class_exists($classNameSanitized)) {
            if (!$className) {
                $className = "[Classname not set]";
            }
            throw new Exception("$className is no valid class name");
        }
    }

    /**
     * Get class name for given filename
     * Only does work from framelix classes
     * @param string $file
     * @return string|null
     */
    public static function getClassNameForFile(string $file): ?string
    {
        if (!file_exists($file)) {
            return null;
        }
        $file = realpath($file);
        $file = str_replace("/", "\\", $file);
        $relativePath = substr($file, strlen(FileUtils::getAppRootPath() . "/modules"));
        $className = "Framelix" . str_replace([
                "\\" . "src" . "\\",
                "\\" . "tests" . "\\"
            ], "\\", $relativePath);
        return substr($className, 0, -4);
    }

    /**
     * Get the framelix module for given class name
     * @param string|object $class
     * @return string
     */
    public static function getModuleForClass(string|object $class): string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        return substr($class, 9, strpos($class, "\\", 9) - 9);
    }
}