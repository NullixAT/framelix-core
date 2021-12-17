<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View\Backend\Setup;

use function dirname;
use function error_reporting;
use function explode;
use function file_exists;
use function implode;
use function ini_set;
use function mb_internal_encoding;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function set_time_limit;
use function spl_autoload_register;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function substr;

use const E_ALL;
use const FRAMELIX_MODULE;

/**
 * Framelix - The beginning
 */
class Framelix
{
    /**
     * Initializes the framework
     */
    public static function init(): void
    {
        // report all errors, everything, we not accept any error
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        // default 60 seconds run time and 128M memory, suitable for most default app calls
        set_time_limit(60);
        ini_set("memory_limit", "128M");
        // everything is utf8
        ini_set("default_charset", "utf-8");
        mb_internal_encoding("UTF-8");
        // disable zlib, should be handled by webserver
        ini_set("zlib.output_compression", 0);

        require_once __DIR__ . "/Utils/FileUtils.php";

        // autoloader for all framework classes
        spl_autoload_register(function (string $className) {
            if (!str_starts_with($className, "Framelix\\")) {
                return false;
            }
            $exp = explode("\\", $className);
            $module = $exp[1];
            unset($exp[0], $exp[1]);
            $rootPath = FileUtils::getModuleRootPath($module);
            // for src classes
            $path = $rootPath . "/src/" . implode("/", $exp) . ".php";
            if (file_exists($path)) {
                require $path;
                return true;
            }
            // for test classes
            $path = $rootPath . "/tests/" . implode("/", $exp) . ".php";
            if (file_exists($path)) {
                require $path;
                return true;
            }
            return false;
        });

        // vendor autoloads
        self::addPs4Autoloader('RobThree\\Auth', __DIR__ . "/../vendor/twofactorauth/lib");

        // exception handling
        $errorClass = Error::class;
        set_error_handler([$errorClass, "onError"], E_ALL);
        set_exception_handler([$errorClass, "onException"]);
        register_shutdown_function([$errorClass, "onShutdown"]);

        Config::loadModule("Framelix");
        Config::loadModule(FRAMELIX_MODULE);

        // setup required, skip everything and init with minimal data
        if (!Request::isCli() && !file_exists(
                FileUtils::getModuleRootPath(FRAMELIX_MODULE . "/config/config-editable.php")
            )) {
            $baseFolder = str_ends_with(
                $_SERVER['REQUEST_URI'],
                "/"
            ) ? substr($_SERVER['REQUEST_URI'], 0, 1) : dirname($_SERVER['REQUEST_URI']);
            if (!str_ends_with($_SERVER['REQUEST_URI'], "/setup")) {
                Url::create($baseFolder . "/setup")->redirect();
            }
            Config::set('languagesSupported', Lang::$coreSupportedLanguages);
            Config::set('languageDefault', Lang::getLanguageByBrowserSettings() ?? 'en');
            Config::set('languageFallback', 'en');
            Config::set('languageMultiple', false);
            Config::set('languageDefaultUser', false);
            Config::set("applicationHttps", ($_SERVER['HTTPS'] ?? null) === 'on');
            Config::set('applicationHost', $_SERVER['HTTP_HOST']);
            Config::set('applicationUrlBasePath', trim(str_replace("/setup", "", $_SERVER['REQUEST_URI']), "/"));
            Lang::$lang = Config::get('languageDefault');
            View::addAvailableView(Setup::class);
        } else {
            Config::load();
            foreach (Config::$loadedModules as $module) {
                View::updateMetadata($module);
                View::addAvailableViewsByModule($module);
            }
            Lang::$lang = Config::get('languageDefault');
            if (!Request::isCli() && Config::get('languageMultiple') && Config::get('languagesSupported')) {
                if (Config::get('languageDefaultUser')) {
                    $userLang = Lang::getLanguageByBrowserSettings();
                    if ($userLang) {
                        Lang::$lang = $userLang;
                    }
                }
            }
        }
    }

    /**
     * Add ps4 autoloader
     * @param string $namespace
     * @param string $folder
     */
    public static function addPs4Autoloader(string $namespace, string $folder)
    {
        spl_autoload_register(function ($class) use ($namespace, $folder) {
            $destinations = [
                $namespace => $folder
            ];
            foreach ($destinations as $prefix => $base_dir) {
                // does the class use the namespace prefix?
                $len = strlen($prefix);
                if (strncmp($prefix, $class, $len) !== 0) {
                    // no, move to the next registered autoloader
                    continue;
                }

                // get the relative class name
                $relative_class = substr($class, $len);

                // replace the namespace prefix with the base directory, replace namespace
                // separators with directory separators in the relative class name, append
                // with .php
                $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

                // if the file exists, require it
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        });
    }
}