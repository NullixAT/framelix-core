<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use JetBrains\PhpStorm\ExpectedValues;

use function call_user_func_array;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function gettype;
use function is_array;
use function is_callable;
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
     * Check if config key exists, even if it is null it returns true
     * @param string $key
     * @return bool
     */
    public static function keyExists(string $key): bool
    {
        return ArrayUtils::keyExists(self::$data, $key);
    }

    /**
     * Get config value
     * @param string $key
     * @param string|null $requiredType If set, then given config key value must be of this type
     *   * = any value but not null
     * @return mixed
     */
    public static function get(
        string $key,
        #[ExpectedValues(['*', 'bool', 'int', 'float', 'string', 'array'])]
        ?string $requiredType = null
    ): mixed {
        $value = ArrayUtils::getValue(self::$data, $key);
        if ($requiredType && $value === null) {
            throw new Exception("Config key '$key' could not be null", ErrorCode::CONFIG_VALUE_INVALID_TYPE);
        }
        if ($value !== null) {
            $valueType = gettype($value);
            if ($valueType === 'boolean') {
                $valueType = 'bool';
            }
            if ($valueType === 'double') {
                $valueType = 'float';
            }
            if ($valueType === 'integer') {
                $valueType = 'int';
            }
            if ($requiredType && $requiredType !== "*" && $valueType !== $requiredType) {
                throw new Exception(
                    "Config key '$key' must be of type '$requiredType' but is '$valueType'",
                    ErrorCode::CONFIG_VALUE_INVALID_TYPE
                );
            }
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
        // load framelix and the current module
        $modules = ["Framelix", FRAMELIX_MODULE];
        foreach ($modules as $module) {
            self::loadModule($module);
        }
        // load all modules that are added in the config
        $modules = self::get("modules");
        if (is_array($modules)) {
            foreach ($modules as $module) {
                self::loadModule($module);
            }
        }
        // load modules that are returned by a configured callable
        $configCallable = Config::get('modulesCallable');
        if ($configCallable && is_callable($configCallable)) {
            $modules = call_user_func_array($configCallable, []);
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
        $hasConfig = false;
        foreach ($files as $file) {
            $config = self::getConfigFromFile($module, $file);
            if (is_array($config)) {
                $hasConfig = true;
                Config::merge($config);
            }
        }
        // only if it has a module config it is considered a real module
        // otherwise just add to allowed autoloading
        if ($hasConfig) {
            self::$loadedModules[$module] = $module;
        }
        Framelix::$allowedAutoloadingModules[$module] = $module;
        $moduleLangFolder = __DIR__ . "/../../$module/lang";
        Lang::addValuesForFolder($moduleLangFolder);
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
    public static function writeConfigToFile(string $module, string $file, array $configData): void
    {
        $configFileData = file_get_contents(
            FileUtils::getModuleRootPath("Framelix") . "/config/config-editable-template.php"
        );
        $configFileData = str_replace('"PLACEHOLDER"', JsonUtils::encode($configData, true), $configFileData);
        $file = FileUtils::getModuleRootPath($module) . "/config/$file";
        file_put_contents($file, $configFileData);
    }
}