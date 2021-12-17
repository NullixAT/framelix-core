<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Throwable;

use function array_key_exists;
use function array_shift;
use function array_values;
use function is_bool;
use function is_string;
use function str_starts_with;

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
     *     Example: *\/5 * * * * php console.php cron
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