<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Zip;
use Throwable;

use function array_combine;
use function array_key_exists;
use function array_shift;
use function array_values;
use function copy;
use function file_exists;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_dir;
use function is_file;
use function is_string;
use function mkdir;
use function readline;
use function readline_add_history;
use function realpath;
use function rename;
use function str_starts_with;
use function unlink;

use const FRAMELIX_MODULE;

/**
 * Console runner
 */
class Console
{
    /**
     * Overriden parameters
     * @var array
     */
    protected static array $overridenParameters = [];

    /**
     * Reset application
     * This does delete EVERY data in the dabasase and delete all configuration settings
     * It cannot be undone
     * @return void
     */
    public static function resetApplication(): void
    {
        if (!Config::isDevMode()) {
            self::red("Can only be executed in devMode");
            return;
        }
        self::question("Are you sure? This cannot be undone!", ['yes']);
        $db = Mysql::get();
        $fetch = $db->fetchAssoc("SHOW TABLE STATUS FROM `{$db->connectionConfig['database']}`");
        foreach ($fetch as $row) {
            $db->query("DROP TABLE `{$row['Name']}`");
        }
        $configFile = FileUtils::getModuleRootPath(FRAMELIX_MODULE) . "/config/config-editable.php";
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }

    /**
     * Update database (Only safe queries)
     * @return void
     */
    public static function updateDatabaseSafe(): void
    {
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $count = 0;
        $queries = $builder->getSafeQueries();
        foreach ($queries as $row) {
            $builder->db->query($row['query']);
            $count++;
        }
        echo $count . " safe queries has been executed\n";
    }

    /**
     * Update database (Only unsafe queries)
     * @return void
     */
    public static function updateDatabaseUnsafe(): void
    {
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $count = 0;
        $queries = $builder->getUnsafeQueries();
        foreach ($queries as $row) {
            $builder->db->query($row['query']);
            $count++;
        }
        echo $count . " unsafe queries has been executed\n";
    }

    /**
     * Running the cronjob (Install to run every 5 minutes)
     * Example: *\/5 * * * * php console.php cron
     * @return void
     */
    public static function cron(): void
    {
        foreach (Config::$loadedModules as $module) {
            $cronClass = "\\Framelix\\$module\\Cron";
            if (class_exists($cronClass) && method_exists($cronClass, "run")) {
                try {
                    $start = microtime(true);
                    call_user_func_array([$cronClass, "run"], []);
                    $diff = microtime(true) - $start;
                    $diff = round($diff * 1000);
                    echo "OK Job $cronClass::run() done in {$diff}ms\n";
                } catch (Throwable $e) {
                    echo "ERR Job $cronClass::run() error: " . $e->getMessage() . "\n";
                }
            } else {
                echo "SKIP $module as no cron handler is installed\n";
            }
        }
    }

    /**
     * Update/install module/app updates by given zip file
     * @param string|null $zipPath If set, this action will update directly without asking for zipfile (is used from web based update)
     * @return void
     */
    public static function installZipPackage(?string $zipPath = null): void
    {
        if (!is_string($zipPath)) {
            $zipPath = self::getParameter('zipPath', 'string');
            // try relative path
            if (!is_file($zipPath)) {
                $zipPath = __DIR__ . "/" . $zipPath;
            }
        }
        if (!file_exists($zipPath)) {
            self::red("$zipPath does not exist");
            return;
        }
        $tmpPath = __DIR__ . "/../tmp/unzip";
        FileUtils::deleteDirectory($tmpPath);
        mkdir($tmpPath);
        Zip::unzip($zipPath, $tmpPath);
        $packageJson = JsonUtils::readFromFile($tmpPath . "/package.json");
        $moduleName = $packageJson['framelix']['module'] ?? null;
        $rootPackage = $packageJson['framelix']['isRootPackage'] ?? null;
        if (!$moduleName && !$rootPackage) {
            self::red("$zipPath is not a valid archive");
            return;
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            $rootDirectory = realpath(__DIR__ . "/../../..");
        } else {
            $rootDirectory = FileUtils::getModuleRootPath($moduleName);
        }
        $filelistNew = JsonUtils::readFromFile($tmpPath . "/filelist.json");
        if (!$filelistNew) {
            self::red(
                "$zipPath has no filelist. Build the archive with the build tools that generate the filelist for you."
            );
            return;
        }
        $filelistNew = array_combine($filelistNew, $filelistNew);
        $filelistExist = file_exists($rootDirectory . "/filelist.json") ? JsonUtils::readFromFile(
            $rootDirectory . "/filelist.json"
        ) : [];
        $filelistExist = array_combine($filelistExist, $filelistExist);
        if (!is_dir($rootDirectory)) {
            mkdir($rootDirectory);
        }
        foreach ($filelistNew as $relativeFile) {
            $tmpFilePath = FileUtils::normalizePath(realpath($tmpPath . "/" . $relativeFile));
            $newPath = $rootDirectory . "/" . $relativeFile;
            if (is_dir($tmpFilePath)) {
                if (!is_dir($newPath)) {
                    mkdir($newPath);
                    self::green('[ADDED] Directory "' . $newPath . '"' . "\n");
                } else {
                    self::yellow('[SKIPPED] Directory "' . $newPath . '" already exist' . "\n");
                }
            } elseif (is_file($tmpFilePath)) {
                if (file_exists($newPath)) {
                    copy($tmpFilePath, $newPath);
                    self::green('[UPDATED] File "' . $newPath . '"' . "\n");
                } else {
                    copy($tmpFilePath, $newPath);
                    self::green('[ADDED] File "' . $newPath . '"' . "\n");
                }
            }
            unset($filelistExist[$relativeFile]);
        }
        foreach ($filelistExist as $fileExist) {
            $newPath = $rootDirectory . "/" . $fileExist;
            if (is_dir($newPath)) {
                self::yellow('[REMOVED] Obsolete Directory "' . $newPath . '"' . "\n");
            } elseif (is_file($newPath)) {
                self::green('[REMOVED] Obsolete File "' . $newPath . '"' . "\n");
                unlink($newPath);
            }
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            rename($tmpPath, "$tmpPath-zips");
            $tmpPath .= "-zips";
            $moduleZipFiles = FileUtils::getFiles($tmpPath . "/modules", "~\.zip$~");
            foreach ($moduleZipFiles as $moduleZipFile) {
                self::installZipPackage($moduleZipFile);
            }
        }
        FileUtils::deleteDirectory($tmpPath);
    }

    /**
     * Draw red text
     * @param string $text
     * @return void
     */
    protected static function red(string $text): void
    {
        echo "\033[31m$text\033[0m";
    }

    /**
     * Draw yellow text
     * @param string $text
     * @return void
     */
    protected static function yellow(string $text): void
    {
        echo "\033[33m$text\033[0m";
    }

    /**
     * Draw green text
     * @param string $text
     * @return void
     */
    protected static function green(string $text): void
    {
        echo "\033[32m$text\033[0m";
    }

    /**
     * Display a message after which the user must enter some text
     * The entered text is returned
     * @param string $message
     * @param string[]|null $availableAnswers Only this answer are accepted, the question will be repeated until the user enter a correct answer
     * @param string|null $defaultAnswer
     * @return string
     */
    protected static function question(
        string $message,
        ?array $availableAnswers = null,
        ?string $defaultAnswer = null
    ): string {
        $readlinePrompt = $message;
        if (is_array($availableAnswers)) {
            $readlinePrompt .= " [" . implode("|", $availableAnswers) . "]";
        }
        if (is_string($defaultAnswer)) {
            $readlinePrompt .= " (Default: $defaultAnswer)";
        }
        $readlinePrompt .= ": ";
        $answer = readline($readlinePrompt);
        if (is_array($availableAnswers) && !in_array($answer, $availableAnswers)) {
            return self::question($message, $availableAnswers, $defaultAnswer);
        }
        readline_add_history($answer);
        return is_string($answer) ? $answer : '';
    }

    /**
     * Override a given command line parameter (Used when invoking a method inside of a method)
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected static function overrideParameter(string $name, mixed $value): void
    {
        self::$overridenParameters[$name] = $value;
    }

    /**
     * Get a single command line parameter
     * Example: --foo bar => 'bar'
     * Example: --foo => true
     * @param string $name
     * @param string|null $requiredParameterType If set, then parameter must be this type, can be: string|bool
     * @return string|bool|null
     */
    protected static function getParameter(string $name, ?string $requiredParameterType = null): string|bool|null
    {
        $arr = self::getParameters($name);
        if (!$arr) {
            if ($requiredParameterType) {
                echo "Missing required parameter '--$name'";
                die();
            }
            return null;
        }
        $param = $arr[0];
        if ($requiredParameterType === 'string' && !is_string($param)) {
            echo "Parameter '--$name' needs to be a string instead of boolean flag";
            die();
        }
        if ($requiredParameterType === 'bool' && !is_bool($param)) {
            echo "Parameter '--$name' needs to be a bool flag instead of string value";
            die();
        }
        return $param;
    }

    /**
     * Get multiple command line parameters with the same name
     * Example: --foo bar --foo zar --foo => ['bar', 'zar', true]
     * @param string $name
     * @return string[]|bool[]
     */
    protected static function getParameters(string $name): array
    {
        if (array_key_exists($name, self::$overridenParameters)) {
            return self::$overridenParameters[$name];
        }
        $args = $_SERVER['argv'];
        array_shift($args);
        array_shift($args);
        $arr = [];
        $validArg = false;
        foreach ($args as $arg) {
            if ($arg === "--" . $name) {
                $validArg = true;
                $arr[$name] = true;
            } elseif ($validArg && !str_starts_with($arg, "--")) {
                $arr[$name] = $arg;
                break;
            }
        }
        return array_values($arr);
    }
}