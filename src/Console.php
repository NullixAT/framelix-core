<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\Utils\Zip;
use Throwable;

use function array_key_exists;
use function array_shift;
use function array_values;
use function copy;
use function count;
use function file_exists;
use function hash_file;
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
use function sleep;
use function str_starts_with;
use function unlink;

use const FRAMELIX_MODULE;

/**
 * Console runner
 */
class Console
{
    /**
     * If set to true, the output will be html formatted (Colors) instead of terminal control codes
     * @var bool
     */
    public static bool $htmlOutput = false;

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
        $queries = $builder->getSafeQueries();
        $builder->executeQueries($queries);
        echo count($queries) . " safe queries has been executed\n";
    }

    /**
     * Update database (Only unsafe queries)
     * @return void
     */
    public static function updateDatabaseUnsafe(): void
    {
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $queries = $builder->getUnsafeQueries();
        $builder->executeQueries($queries);
        echo count($queries) . " unsafe queries has been executed\n";
    }

    /**
     * Check for app updates (Modules and App)
     * @return void
     */
    public static function checkAppUpdates(): void
    {
        // todo integrate
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
            if (class_exists($cronClass) && method_exists($cronClass, "runCron")) {
                try {
                    $start = microtime(true);
                    call_user_func_array([$cronClass, "runCron"], []);
                    $diff = microtime(true) - $start;
                    $diff = round($diff * 1000);
                    $info = "[OK] Job $cronClass::run() done in {$diff}ms";
                    self::green("$info\n");
                } catch (Throwable $e) {
                    $info = "[ERR] Job $cronClass::run() error: " . $e->getMessage();
                    self::red("$info\n");
                }
                $logCategory = SystemEventLog::CATEGORY_CRON;
                if (Config::get('systemEventLog[' . $logCategory . ']')) {
                    SystemEventLog::create($logCategory, null, [$info]);
                }
            } else {
                self::yellow("[SKIP] $module as no cron handler is installed\n");
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

        // check if is root package, then unpack package.zip which contains everything
        if (file_exists($tmpPath . "/package.zip")) {
            $tmpPathNew = $tmpPath . "-2";
            FileUtils::deleteDirectory($tmpPathNew);
            mkdir($tmpPathNew);
            Zip::unzip($tmpPath . "/package.zip", $tmpPathNew);
            FileUtils::deleteDirectory($tmpPath);
            rename($tmpPathNew, $tmpPath);
        }

        $packageJson = JsonUtils::readFromFile($tmpPath . "/package.json");
        $moduleName = $packageJson['framelix']['module'] ?? null;
        $rootPackage = $packageJson['framelix']['isRootPackage'] ?? null;
        if (!$moduleName && !$rootPackage) {
            self::red("$zipPath is not a valid archive");
            return;
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            $rootDirectory = realpath(__DIR__ . "/../../..");
            echo "Update App to " . $packageJson['version'] . "\n";
        } else {
            $rootDirectory = FileUtils::getModuleRootPath($moduleName);
            echo "Update Module '" . $moduleName . "' to " . $packageJson['version'] . "\n";
        }
        $filelistNew = JsonUtils::readFromFile($tmpPath . "/filelist.json");
        if (!$filelistNew) {
            self::red(
                "$zipPath has no filelist. Build the archive with the build tools that generate the filelist for you."
            );
            return;
        }
        $filelistExist = file_exists($rootDirectory . "/filelist.json") ? JsonUtils::readFromFile(
            $rootDirectory . "/filelist.json"
        ) : [];
        if (!is_dir($rootDirectory)) {
            mkdir($rootDirectory);
        }
        $counts = [
            'addedDirectory' => 0,
            'skippedDirectory' => 0,
            'deletedDirectory' => 0,
            'addedFile' => 0,
            'updatedFile' => 0,
            'skippedFile' => 0,
            'deletedFile' => 0,
        ];
        foreach ($filelistNew as $relativeFile => $hash) {
            $tmpFilePath = FileUtils::normalizePath(realpath($tmpPath . "/" . $relativeFile));
            $newPath = $rootDirectory . "/" . $relativeFile;
            if (is_dir($tmpFilePath)) {
                if (!is_dir($newPath)) {
                    mkdir($newPath);
                    self::green('[ADDED] Directory "' . $newPath . '"' . "\n");
                    $counts['addedDirectory']++;
                } else {
                    self::yellow('[SKIPPED] Directory "' . $newPath . '" already exist' . "\n");
                    $counts['skippedDirectory']++;
                }
            } elseif (is_file($tmpFilePath)) {
                if (file_exists($newPath)) {
                    if (hash_file("crc32", $newPath) === $hash) {
                        self::yellow('[SKIPPED] File "' . $newPath . '" exist and is not changed' . "\n");
                        $counts['skippedFile']++;
                    } else {
                        copy($tmpFilePath, $newPath);
                        self::green('[UPDATED] File "' . $newPath . '"' . "\n");
                        $counts['updatedFile']++;
                    }
                } else {
                    copy($tmpFilePath, $newPath);
                    self::green('[ADDED] File "' . $newPath . '"' . "\n");
                    $counts['addedFile']++;
                }
            }
            unset($filelistExist[$relativeFile]);
        }
        foreach ($filelistExist as $fileExist => $hash) {
            $newPath = $rootDirectory . "/" . $fileExist;
            if (is_dir($newPath)) {
                self::yellow('[REMOVED] Obsolete Directory "' . $newPath . '"' . "\n");
                $counts['deletedDirectory']++;
            } elseif (is_file($newPath)) {
                self::green('[REMOVED] Obsolete File "' . $newPath . '"' . "\n");
                $counts['deletedFile']++;
                unlink($newPath);
            }
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            rename($tmpPath, "$tmpPath-zips");
            $tmpPath .= "-zips";
            $moduleZipFiles = FileUtils::getFiles($tmpPath . "/modules", "~\.zip$~");
            foreach ($moduleZipFiles as $moduleZipFile) {
                self::overrideParameter('skipDatabaseUpdate', [true]);
                self::installZipPackage($moduleZipFile);
                self::overrideParameter('skipDatabaseUpdate', [false]);
            }
        }
        FileUtils::deleteDirectory($tmpPath);
        $labels = [
            'addedDirectory' => 'New directories',
            'skippedDirectory' => 'Skipped directories',
            'deletedDirectory' => 'Deleted directories',
            'addedFile' => 'New files',
            'updatedFile' => 'Updated files',
            'skippedFile' => 'Skipped files',
            'deletedFile' => 'Deleted files'
        ];
        foreach ($counts as $type => $count) {
            echo "$count " . $labels[$type] . "\n";
        }
        if (!self::getParameter('skipDatabaseUpdate')) {
            // update database
            // wait 3 seconds to prevent opcache in default configs
            echo "Update database\n";
            sleep(3);
            $shell = Shell::prepare("php {*}", [
                __DIR__ . "/../console.php",
                "updateDatabaseSafe"
            ])->execute();
            echo implode("\n", $shell->output) . "\n";
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            self::green("App Update completed\n");
        } else {
            self::green("Module Update completed\n");
        }
    }

    /**
     * Draw red text
     * @param string $text
     * @return void
     */
    protected static function red(string $text): void
    {
        if (self::$htmlOutput) {
            echo '<span style="color:var(--color-error-text)">' . $text . '</span>';
            return;
        }
        echo "\033[31m$text\033[0m";
    }

    /**
     * Draw yellow text
     * @param string $text
     * @return void
     */
    protected static function yellow(string $text): void
    {
        if (self::$htmlOutput) {
            echo '<span style="color:var(--color-warning-text)">' . $text . '</span>';
            return;
        }
        echo "\033[33m$text\033[0m";
    }

    /**
     * Draw green text
     * @param string $text
     * @return void
     */
    protected static function green(string $text): void
    {
        if (self::$htmlOutput) {
            echo '<span style="color:var(--color-success-text)">' . $text . '</span>';
            return;
        }
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
     * @param array|null $value
     * @return void
     */
    protected static function overrideParameter(string $name, ?array $value): void
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
                Framelix::stop();
            }
            return null;
        }
        $param = $arr[0];
        if ($requiredParameterType === 'string' && !is_string($param)) {
            echo "Parameter '--$name' needs to be a string instead of boolean flag";
            Framelix::stop();
        }
        if ($requiredParameterType === 'bool' && !is_bool($param)) {
            echo "Parameter '--$name' needs to be a bool flag instead of string value";
            Framelix::stop();
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
        $args = $_SERVER['argv'] ?? [];
        if (!$args) {
            return [];
        }
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