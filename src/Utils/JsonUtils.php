<?php

namespace Framelix\Framelix\Utils;

use function header;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * Json utilities for frequent tasks
 */
class JsonUtils
{
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
     * Output given data, set correct content type and stop code execution
     * @param mixed $data
     * @return never
     */
    public static function output(mixed $data): never
    {
        header("content-type: application/json");
        echo self::encode($data);
        die();
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