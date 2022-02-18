<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\Utils\Zip;
use JetBrains\PhpStorm\ExpectedValues;
use Throwable;

use function array_key_exists;
use function array_shift;
use function array_unshift;
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
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strpos;
use function substr;
use function unlink;
use function version_compare;

use const FRAMELIX_MODULE;

/**
 * Console runner
 *
 * As this do very complicated tasks ignore coverage for now
 * @todo At least tests for zip package installation should be added soon
 * @codeCoverageIgnore
 */
class Console
{
    // the console.php script
    public const CONSOLE_SCRIPT = __DIR__ . "/../console.php";

    /**
     * Overriden parameters
     * @var array
     */
    protected static array $overridenParameters = [];

    /**
     * Reset application
     * This does delete EVERY data in the dabasase and delete all configuration settings
     * It cannot be undone
     * @return int Status Code, 0 = success
     */
    public static function resetApplication(): int
    {
        if (!Config::isDevMode()) {
            self::error("Can only be executed in devMode");
            return 1;
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
        return 0;
    }

    /**
     * Update database (Only safe queries)
     * @return int Status Code, 0 = success
     */
    public static function updateDatabaseSafe(): int
    {
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $queries = $builder->getSafeQueries();
        $builder->executeQueries($queries);
        self::success(count($queries) . " safe queries has been executed");
        return 0;
    }

    /**
     * Update database (Only unsafe queries)
     * @return int Status Code, 0 = success
     */
    public static function updateDatabaseUnsafe(): int
    {
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $queries = $builder->getUnsafeQueries();
        $builder->executeQueries($queries);
        self::success(count($queries) . " unsafe queries has been executed");
        return 0;
    }

    /**
     * Check for app updates
     * @return int Status Code, 0 = success
     */
    public static function checkAppUpdate(): int
    {
        $updateAppUpdateFile = AppUpdate::UPDATE_CACHE_FILE;
        if (file_exists($updateAppUpdateFile)) {
            unlink($updateAppUpdateFile);
        }
        $packageJson = JsonUtils::getPackageJson(null);
        if ($packageJson) {
            try {
                $currentVersion = $packageJson['version'];
                if (isset($packageJson['repository']['url'])) {
                    $url = $packageJson['repository']['url'];
                    if (str_starts_with($url, "git+") || str_contains($url, "github.com")) {
                        if (str_starts_with($url, "git+")) {
                            $url = substr($url, 4);
                        }
                        if (str_ends_with($url, ".git")) {
                            $url = substr($url, strpos($url, 'github.com/') + 11, -4);
                            $browser = Browser::create();
                            $browser->url = 'https://api.github.com/repos/' . $url . '/releases';
                            $browser->sendRequest();
                            $releaseData = $browser->getResponseJson();
                            foreach ($releaseData as $row) {
                                if ($row['draft']) {
                                    continue;
                                }
                                if (version_compare($row['tag_name'], $currentVersion, '>')) {
                                    $currentVersion = $row['tag_name'];
                                    JsonUtils::writeToFile($updateAppUpdateFile, $row);
                                }
                            }
                        }
                    }
                }
                if ($currentVersion && $packageJson['version'] !== $currentVersion) {
                    self::line('New version ' . $currentVersion . ' available');
                } else {
                    self::line('No update available');
                }
            } catch (Throwable $e) {
                self::error($e->getMessage());
                return 1;
            }
        }
        return 0;
    }

    /**
     * Check and install app update, if there is one available
     * @param array|null $summaryData If set, summary data will be saved into this variable
     * @return int Status Code, 0 = success
     */
    public static function checkAndInstallAppUpdate(?array &$summaryData = null): int
    {
        self::checkAppUpdate();
        $updateAppUpdateFile = AppUpdate::UPDATE_CACHE_FILE;
        if (!file_exists($updateAppUpdateFile)) {
            return 0;
        }
        $updateData = JsonUtils::readFromFile($updateAppUpdateFile);
        if (isset($updateData['assets'])) {
            foreach ($updateData['assets'] as $row) {
                if ($row['name'] === 'release-' . $updateData['tag_name'] . ".zip") {
                    $browser = Browser::create();
                    $browser->url = $row['browser_download_url'];
                    $browser->sendRequest();
                    $updateAppUpdateZipFile = substr($updateAppUpdateFile, 0, -5) . ".zip";
                    file_put_contents(
                        $updateAppUpdateZipFile,
                        $browser->getResponseText()
                    );
                    Console::installZipPackage($updateAppUpdateZipFile, $summaryData);
                    unlink($updateAppUpdateZipFile);
                    unlink($updateAppUpdateFile);
                }
            }
        }
        return 0;
    }

    /**
     * Running the cronjob (Install to run every 5 minutes)
     * Example: *\/5 * * * * php console.php cron
     * @return int Status Code, 0 = success
     */
    public static function cron(): int
    {
        $exitCode = 0;
        foreach (Config::$loadedModules as $module) {
            $cronClass = "\\Framelix\\$module\\Cron";
            if (class_exists($cronClass) && method_exists($cronClass, "runCron")) {
                try {
                    $start = microtime(true);
                    call_user_func_array([$cronClass, "runCron"], []);
                    $diff = microtime(true) - $start;
                    $diff = round($diff * 1000);
                    $info = "[OK] Job $cronClass::run() done in {$diff}ms";
                    self::success("$info\n");
                } catch (Throwable $e) {
                    $info = "[ERR] Job $cronClass::run() error: " . $e->getMessage();
                    self::error("$info\n");
                    $exitCode = 1;
                }
                $logCategory = SystemEventLog::CATEGORY_CRON;
                if (Config::get('systemEventLog[' . $logCategory . ']')) {
                    SystemEventLog::create($logCategory, null, [$info]);
                }
            } else {
                self::warn("[SKIP] $module as no cron handler is installed\n");
            }
        }
        return $exitCode;
    }

    /**
     * Update/install module/app updates by given zip file
     * @param string|null $zipPath If set, this action will update directly without asking for zipfile (is used from web based update)
     * @param array|null $summaryData If set, summary data will be saved into this variable
     * @return int Status Code, 0 = success
     */
    public static function installZipPackage(?string $zipPath = null, ?array &$summaryData = null): int
    {
        if (!is_string($zipPath)) {
            $zipPath = self::getParameter('zipPath', 'string');
            // try relative path
            if (!is_file($zipPath)) {
                $zipPath = __DIR__ . "/" . $zipPath;
            }
        }
        if (!file_exists($zipPath)) {
            self::error("$zipPath does not exist");
            return 1;
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
            self::error("$zipPath is not a valid archive");
            return 1;
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            $rootDirectory = realpath(__DIR__ . "/../../..");
            self::line("Update App to " . $packageJson['version']);
        } else {
            $rootDirectory = FileUtils::getModuleRootPath($moduleName);
            self::line("Update Module '" . $moduleName . "' to " . $packageJson['version']);
        }
        $filelistNew = JsonUtils::readFromFile($tmpPath . "/filelist.json");
        if (!$filelistNew) {
            self::error(
                "$zipPath has no filelist. Build the archive with the build tools that generate the filelist for you."
            );
            return 1;
        }
        $filelistExist = file_exists($rootDirectory . "/filelist.json") ? JsonUtils::readFromFile(
            $rootDirectory . "/filelist.json"
        ) : [];
        if (!is_dir($rootDirectory)) {
            mkdir($rootDirectory);
        }
        if (!is_array($summaryData)) {
            $summaryData = [];
        }
        $keys = [
            'dir_added',
            'file_added',
            'file_updated',
            'dir_deleted',
            'file_deleted',
            'file_skipped',
            'dir_skipped',
        ];
        foreach ($keys as $key) {
            if (!isset($summaryData[$key])) {
                $summaryData[$key] = 0;
            }
        }
        foreach ($filelistNew as $relativeFile => $hash) {
            $tmpFilePath = FileUtils::normalizePath(realpath($tmpPath . "/" . $relativeFile));
            $newPath = $rootDirectory . "/" . $relativeFile;
            if (is_dir($tmpFilePath)) {
                if (!is_dir($newPath)) {
                    mkdir($newPath);
                    self::line('[ADDED] Directory "' . $newPath . '"');
                    $summaryData['dir_added']++;
                } else {
                    self::line('[SKIPPED] Directory "' . $newPath . '" already exist');
                    $summaryData['dir_skipped']++;
                }
            } elseif (is_file($tmpFilePath)) {
                if (file_exists($newPath)) {
                    if (hash_file("crc32", $newPath) === $hash) {
                        self::line('[SKIPPED] File "' . $newPath . '" exist and is not changed');
                        $summaryData['file_skipped']++;
                    } else {
                        copy($tmpFilePath, $newPath);
                        self::line('[UPDATED] File "' . $newPath . '"');
                        $summaryData['file_updated']++;
                    }
                } else {
                    copy($tmpFilePath, $newPath);
                    self::line('[ADDED] File "' . $newPath . '"');
                    $summaryData['file_added']++;
                }
            }
            unset($filelistExist[$relativeFile]);
        }
        foreach ($filelistExist as $fileExist => $hash) {
            $newPath = $rootDirectory . "/" . $fileExist;
            if (is_dir($newPath)) {
                self::line('[REMOVED] Obsolete Directory "' . $newPath . '"');
                $summaryData['dir_deleted']++;
            } elseif (is_file($newPath)) {
                self::line('[REMOVED] Obsolete File "' . $newPath . '"');
                $summaryData['file_deleted']++;
                unlink($newPath);
            }
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            FileUtils::deleteDirectory("$tmpPath-zips");
            rename($tmpPath, "$tmpPath-zips");
            $tmpPath .= "-zips";
            $moduleZipFiles = FileUtils::getFiles($tmpPath . "/modules", "~\.zip$~");
            foreach ($moduleZipFiles as $moduleZipFile) {
                self::overrideParameter('skipDatabaseUpdate', [true]);
                self::installZipPackage($moduleZipFile, $summaryData);
                self::overrideParameter('skipDatabaseUpdate', [false]);
            }
        }
        FileUtils::deleteDirectory($tmpPath);
        $labels = [
            'dir_added' => 'New directories',
            'dir_skipped' => 'Skipped directories',
            'dir_deleted' => 'Deleted directories',
            'file_added' => 'New files',
            'file_updated' => 'Updated files',
            'file_skipped' => 'Skipped files',
            'file_deleted' => 'Deleted files'
        ];
        foreach ($summaryData as $type => $count) {
            self::line("$count " . $labels[$type]);
        }
        if (!self::getParameter('skipDatabaseUpdate')) {
            // update database
            // wait 3 seconds to prevent opcache in default configs
            self::line("Update database");
            sleep(3);
            $shell = Console::callMethodInSeparateProcess('updateDatabaseSafe');
            self::line(implode("\n", $shell->output));
        }
        if ($packageJson['framelix']['isRootPackage'] ?? null) {
            self::success("App Update completed");
        } else {
            self::success("Module Update completed");
        }
        return 0;
    }

    /**
     * Call console script via php command line interpreter in a separate process
     * @param string $methodName
     * @param array|null $parameters
     * @return Shell
     */
    public static function callMethodInSeparateProcess(string $methodName, ?array $parameters = null): Shell
    {
        if (!is_array($parameters)) {
            $parameters = [];
        }
        array_unshift($parameters, $methodName);
        array_unshift($parameters, self::CONSOLE_SCRIPT);
        $shell = Shell::prepare("php {*}", $parameters);
        $shell->execute();
        return $shell;
    }

    /**
     * Draw error text in red
     * @param string $text
     * @return void
     */
    protected static function error(string $text): void
    {
        echo "\e[31m[ERROR] $text\e[0m\n";
    }

    /**
     * Draw a warn text
     * @param string $text
     * @return void
     */
    protected static function warn(string $text): void
    {
        echo "\e[33m[WARN] $text\e[0m\n";
    }

    /**
     * Draw a success text
     * @param string $text
     * @return void
     */
    protected static function success(string $text): void
    {
        echo "\e[32m[SUCCESS] $text\e[0m\n";
    }

    /**
     * Draw a line with given text
     * @param string $text
     * @return void
     */
    protected static function line(string $text): void
    {
        echo "$text\n";
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
    protected static function getParameter(
        string $name,
        #[ExpectedValues(values: ["string", "bool"])]
        ?string $requiredParameterType = null
    ): string|bool|null {
        $arr = self::getParameters($name);
        if (!$arr) {
            if ($requiredParameterType) {
                self::error("Missing required parameter '--$name'");
                Framelix::stop();
            }
            return null;
        }
        $param = $arr[0];
        if ($requiredParameterType === 'string' && !is_string($param)) {
            self::error("Parameter '--$name' needs to be a string instead of boolean flag");
            Framelix::stop();
        }
        if ($requiredParameterType === 'bool' && !is_bool($param)) {
            self::error("Parameter '--$name' needs to be a bool flag instead of string value");
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