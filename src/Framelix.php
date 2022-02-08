<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View\Backend\Setup;

use function dirname;
use function error_reporting;
use function explode;
use function file_exists;
use function implode;
use function ini_set;
use function mb_internal_encoding;
use function ob_get_level;
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
     * Class autoloading allowed modules
     * @var string[]
     */
    public static array $allowedAutoloadingModules = [];

    /**
     * Initializes the framework
     * @codeCoverageIgnore
     */
    public static function init(): void
    {
        // report all errors, everything, we not accept any error
        error_reporting(E_ALL);
        ini_set("display_errors", '1');
        // default 60 seconds run time and 128M memory, suitable for most default app calls
        set_time_limit(60);
        // everything is utf8
        ini_set("default_charset", "utf-8");
        mb_internal_encoding("UTF-8");
        // disable zlib, should be handled by webserver
        ini_set("zlib.output_compression", '0');

        require_once __DIR__ . "/Utils/Buffer.php";
        require_once __DIR__ . "/Utils/FileUtils.php";
        Buffer::$startBufferIndex = ob_get_level();

        // autoloader for all framework classes
        spl_autoload_register(function (string $className): void {
            if (!str_starts_with($className, "Framelix\\")) {
                return;
            }
            $exp = explode("\\", $className);
            $module = $exp[1];
            // ignore modules that are not yet loaded
            if (
                $module !== "Framelix"
                && $module !== FRAMELIX_MODULE
                && !isset(self::$allowedAutoloadingModules[$module])
            ) {
                return;
            }
            unset($exp[0], $exp[1]);
            $rootPath = FileUtils::getModuleRootPath($module);
            // for src classes
            $path = $rootPath . "/src/" . implode("/", $exp) . ".php";
            if (file_exists($path)) {
                require $path;
            }
        });

        // integrated vendor autoloads
        self::addPs4Autoloader('RobThree\\Auth', __DIR__ . "/../vendor/twofactorauth/lib");

        // exception handling
        $errorClass = ErrorHandler::class;
        set_error_handler([$errorClass, "onError"], E_ALL);
        set_exception_handler([$errorClass, "onException"]);
        register_shutdown_function([$errorClass, "onShutdown"]);

        Config::loadModule("Framelix");
        Config::loadModule(FRAMELIX_MODULE);

        if (!self::isCli()) {
            // set memory limit to 128M as it is enough for almost every use case
            // increase it where it is required
            ini_set("memory_limit", "128M");
        }

        // setup required, skip everything and init with minimal data
        if (!self::isCli() && !file_exists(
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
            Config::set("applicationHttps", Request::isHttps());
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
            if (!self::isCli() && Config::get('languageMultiple') && Config::get('languagesSupported')) {
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
     * Is app running in command line mode
     * @return bool
     */
    public static function isCli(): bool
    {
        return php_sapi_name() === "cli";
    }

    /**
     * Is app running under windows
     * @return bool
     */
    public static function isWindows(): bool
    {
        return str_starts_with(PHP_OS, 'WIN');
    }

    /**
     * Add ps4 autoloader
     * @param string $namespace
     * @param string $folder
     * @codeCoverageIgnore
     */
    public static function addPs4Autoloader(string $namespace, string $folder): void
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

    /**
     * Stop script execution
     * This exception does not log any error, it just stops script execution in current scope
     * It is preferred over die()/exit() as it allows unit tests to finalize
     * @return never
     */
    public static function stop(): never
    {
        throw new StopException();
    }
}