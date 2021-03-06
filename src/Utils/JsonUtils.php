<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Network\Response;

use function file_exists;
use function json_encode;

use const FRAMELIX_APP_ROOT;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * Json utilities for frequent tasks
 */
class JsonUtils
{
    /**
     * Internal cache
     * @var array
     */
    private static $cache = [];

    /**
     * Get package json data
     * @param string|null $module If null, then take package.json of app root
     * @return array|null Null if no package.json exists
     */
    public static function getPackageJson(?string $module): ?array
    {
        $cacheKey = (string)$module;
        if (ArrayUtils::keyExists(self::$cache, $cacheKey)) {
            return self::$cache[$cacheKey];
        }
        if ($module === null) {
            $path = FRAMELIX_APP_ROOT . "/package.json";
        } else {
            $path = FRAMELIX_APP_ROOT . "/modules/$module/package.json";
        }
        if (!file_exists($path)) {
            self::$cache[$cacheKey] = null;
            return null;
        }
        self::$cache[$cacheKey] = self::readFromFile($path);
        return self::$cache[$cacheKey];
    }

    /**
     * Write to file
     * @param string $path
     * @param mixed $data
     * @param bool $prettyPrint
     */
    public static function writeToFile(string $path, mixed $data, bool $prettyPrint = false): void
    {
        file_put_contents($path, self::encode($data, $prettyPrint));
    }

    /**
     * Read from file
     * @param string $path
     * @return mixed
     */
    public static function readFromFile(string $path): mixed
    {
        return self::decode(file_get_contents($path));
    }

    /**
     * Output given data and set correct content type
     * @param mixed $data
     */
    public static function output(mixed $data): void
    {
        Response::header("content-type: application/json");
        echo self::encode($data);
    }

    /**
     * Encode
     * @param mixed $data
     * @param bool $prettyPrint
     * @return string
     */
    public static function encode(mixed $data, bool $prettyPrint = false): string
    {
        $options = JSON_THROW_ON_ERROR;
        if ($prettyPrint) {
            $options = $options | JSON_PRETTY_PRINT;
        }
        return json_encode($data, $options);
    }

    /**
     * Decode
     * @param string $data
     * @return mixed
     */
    public static function decode(string $data): mixed
    {
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}