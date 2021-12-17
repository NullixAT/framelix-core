<?php

namespace Framelix\Framelix;

use Exception;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function str_replace;
use function str_starts_with;

use const FRAMELIX_MODULE;

/**
 * Config
 */
class Config
{
    /**
     * Loaded modules
     * @var string[]
     */
    public static array $loadedModules = [];

    /**
     * Config data
     * @var array|null
     */
    public static ?array $data = null;

    /**
     * Is dev mode
     * @return bool
     */
    public static function isDevMode(): bool
    {
        return self::get("devMode") ?? false;
    }

    /**
     * Get config value
     * @param string $key
     * @param bool $throwException If true, then throw exception if key doesn't exist
     * @return mixed
     */
    public static function get(string $key, bool $throwException = false): mixed
    {
        $value = ArrayUtils::getValue(self::$data, $key);
        if ($value === null && $throwException) {
            throw new Exception("Missing config key '$key'");
        }
        return $value;
    }

    /**
     * Set config value
     * Does override existing value
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, mixed $value): void
    {
        ArrayUtils::setValue(self::$data, $key, $value);
    }

    /**
     * Merge config value
     * Does merge existing values with new values
     * @param mixed $value
     */
    public static function merge(mixed $value): void
    {
        self::$data = ArrayUtils::merge(self::$data, $value);
    }

    /**
     * Load config from disk
     */
    public static function load(): void
    {
        // first, load framelix and the current module
        $modules = ["Framelix", FRAMELIX_MODULE];
        foreach ($modules as $module) {
            self::loadModule($module);
        }
        // second load all modules that are added in the config
        $modules = self::get("modules");
        if (is_array($modules)) {
            foreach ($modules as $module) {
                self::loadModule($module);
            }
        }
    }

    /**
     * Load config from given module
     * @param string $module
     */
    public static function loadModule(string $module): void
    {
        if (isset(self::$loadedModules[$module])) {
            return;
        }
        $files = [
            "config-module.php",
            "config-editable.php",
        ];
        foreach ($files as $file) {
            $config = self::getConfigFromFile($module, $file);
            if (is_array($config)) {
                Config::merge($config);
            }
        }
        self::$loadedModules[$module] = $module;
    }

    /**
     * Get raw config array from given file
     * @param string $module
     * @param string $file
     * @return array|null
     */
    public static function getConfigFromFile(string $module, string $file): ?array
    {
        $file = FileUtils::getModuleRootPath($module) . "/config/$file";
        if (!file_exists($file)) {
            return null;
        }
        $lines = file($file);
        $json = null;
        foreach ($lines as $line) {
            if ($json === null && str_starts_with($line, '<script')) {
                $json = '';
                continue;
            }
            if ($json !== null && str_starts_with($line, '</script>')) {
                break;
            }
            if ($json !== null) {
                $json .= $line;
            }
        }
        return JsonUtils::decode($json);
    }

    /**
     * Write config to given config file
     * This creates a valid php file which later can be directly include via require/include
     * @param string $module
     * @param string $file
     * @param array $configData
     */
    public static function writetConfigToFile(string $module, string $file, array $configData): void
    {
        $configFileData = file_get_contents(
            FileUtils::getModuleRootPath("Framelix") . "/config/config-editable-template.php"
        );
        $configFileData = str_replace('"PLACEHOLDER"', JsonUtils::encode($configData, true), $configFileData);
        $file = FileUtils::getModuleRootPath($module) . "/config/$file";
        file_put_contents($file, $configFileData);
    }
}