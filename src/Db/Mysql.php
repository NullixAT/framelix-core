<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\ObjectTransformable;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Utils\ArrayUtils;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use Throwable;

use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function mb_substr;
use function mysqli_affected_rows;
use function mysqli_insert_id;
use function mysqli_query;
use function mysqli_real_escape_string;
use function mysqli_report;
use function preg_match_all;
use function reset;
use function str_replace;

use const MYSQLI_NUM;
use const MYSQLI_REPORT_ALL;

/**
 * Mysql database handling
 * This is the framelix default and is used internally everywhere
 */
class Mysql
{
    /**
     * Instances
     * @var self[]
     */
    public static array $instances = [];

    /**
     * For debugging you log executed queries into $executedQueries
     * @var bool
     */
    public static $logExecutedQueries = false;

    /**
     * The connection config values
     * @var array
     */
    public array $connectionConfig = [];

    /**
     * MySQLi connection resource
     * @var mysqli|null
     */
    public ?mysqli $mysqli = null;

    /**
     * Last query result
     * @var bool|mysqli_result
     */
    public bool|mysqli_result $lastResult = false;

    /**
     * The id for this instance
     * @var string
     */
    public string $id;

    /**
     * Executed queries
     * @var array
     */
    public array $executedQueries = [];

    /**
     * Executed queries count
     * @var int
     */
    public int $executedQueriesCount = 0;

    /**
     * Get mysql instance for given id
     * @param string $id
     * @param bool $connect If true than connect instantly
     * @return Mysql
     */
    public static function get(string $id = "default", bool $connect = true): Mysql
    {
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }
        $instance = new self();
        $instance->id = $id;
        if ($connect) {
            $instance->connect();
        }
        self::$instances[$id] = $instance;
        return $instance;
    }

    /**
     * Escape any value for database usage
     * @param mixed $value
     * @return string|int|float
     */
    public function escapeValue(mixed $value): string|int|float
    {
        if (is_object($value)) {
            if ($value instanceof ObjectTransformable) {
                $value = $value->getDbValue();
            } else {
                $value = (string)$value;
            }
        }
        if ($value === null) {
            return 'NULL';
        }
        if (is_array($value)) {
            $arr = [];
            foreach ($value as $v) {
                $arr[] = $this->escapeValue($v);
            }
            return "(" . implode(", ", $arr) . ")";
        }
        if (is_float($value)) {
            return $value;
        }
        if (is_bool($value) || is_int($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            return '"' . mysqli_real_escape_string($this->mysqli, $value) . '"';
        }
        throw new Exception("Unsupported value for database parameters", ErrorCode::MYSQL_UNSUPPORTED_DB_PARAMETER);
    }

    /**
     * Connect to database if not yet connected
     */
    public function connect(): void
    {
        // already connected
        if ($this->mysqli) {
            return;
        }
        $configKey = 'database[' . $this->id . ']';
        $databaseConfig = Config::get($configKey, 'array');
        try {
            mysqli_report(MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX);
            $this->connectionConfig = [
                "host" => $databaseConfig['host'] ?? "localhost",
                "username" => $databaseConfig['username'],
                "password" => $databaseConfig['password'],
                "database" => $databaseConfig['database'],
                "port" => $databaseConfig['port'] ?: 3306,
                "socket" => $databaseConfig['socket'] ?? null,
            ];
            $this->mysqli = new mysqli(
                $this->connectionConfig['host'],
                $this->connectionConfig['username'],
                $this->connectionConfig['password'],
                $this->connectionConfig['database'],
                $this->connectionConfig['port'],
                $this->connectionConfig['socket'] ?: null
            );
        } catch (mysqli_sql_exception $e) {
            throw new Exception($e->getMessage(), ErrorCode::MYSQL_CONNECT_ERROR);
        }
        $this->mysqli->set_charset($databaseConfig['charset'] ?? 'utf8mb4');
    }

    /**
     * Execute an insert query
     * @param string $table
     * @param array $values
     * @param string $insertMethod Could be INSERT or REPLACE
     * @return bool
     */
    public function insert(string $table, array $values, string $insertMethod = "INSERT"): bool
    {
        $query = $insertMethod . " `$table` (";
        foreach ($values as $key => $value) {
            $query .= "`$key`, ";
        }
        $query = mb_substr($query, 0, -2) . ") VALUES (";
        foreach ($values as $value) {
            $query .= $this->escapeValue($value) . ", ";
        }
        $query = mb_substr($query, 0, -2) . ")";
        return $this->query($query);
    }

    /**
     * Execute a delete query
     * @param string $table
     * @param string $condition The WHERE condition, need to be set, set to 1 if you want all rows affected
     * @param array|null $conditionParameters
     * @return bool
     */
    public function delete(string $table, string $condition, ?array $conditionParameters = null): bool
    {
        $condition = $this->replaceParameters($condition, $conditionParameters);
        $query = "DELETE FROM `$table` WHERE $condition";
        return $this->query($query);
    }

    /**
     * Execute an update query
     * @param string $table
     * @param array $values
     * @param string $condition The WHERE condition, need to be set, set to 1 if you want all rows affected
     * @param array|null $conditionParameters
     * @return bool
     */
    public function update(string $table, array $values, string $condition, ?array $conditionParameters = null): bool
    {
        $condition = $this->replaceParameters($condition, $conditionParameters);
        $query = "UPDATE `$table` SET ";
        foreach ($values as $key => $value) {
            $query .= "`$key` = " . $this->escapeValue($value) . ", ";
        }
        $query = mb_substr($query, 0, -2) . " WHERE " . $condition;
        return $this->query($query);
    }

    /**
     * Execute the query
     * @param string $query
     * @param array|null $parameters
     * @return bool|mysqli_result
     */
    public function query(string $query, ?array $parameters = null): bool|mysqli_result
    {
        // replace php class names to real table names
        preg_match_all("~`(Framelix\\\\[a-zA-Z0-9_\\\\]+)`~", $query, $classNames);
        foreach ($classNames[0] as $key => $search) {
            $tableName = Storable::getTableName($classNames[1][$key]);
            $query = str_replace($search, '`' . $tableName . '`', $query);
        }
        $query = $this->replaceParameters($query, $parameters);
        return $this->queryRaw($query);
    }

    /**
     * Execute the raw query without any framework modification
     * @param string $query
     * @return bool|mysqli_result
     */
    public function queryRaw(string $query): bool|mysqli_result
    {
        try {
            $this->lastResult = mysqli_query($this->mysqli, $query);
            // this code was unable to reproduce in unit tests
            // every mysql error should throw an exception
            // if it does not but result is still false, we throw by hand
            // maybe its a legacy behaviour of some mysql drivers
            // @codeCoverageIgnoreStart
            if (!$this->lastResult) {
                throw new Exception("No Mysql Result");
            }
            // @codeCoverageIgnoreEnd
        } catch (Throwable $e) {
            $errorMessage = "Mysql Error: " . $e->getMessage();
            if (Config::isDevMode()) {
                $errorMessage .= " in query: " . $query;
            }
            throw new Exception($errorMessage, ErrorCode::MYSQL_QUERY_ERROR);
        }
        $this->executedQueriesCount++;
        if (self::$logExecutedQueries) {
            $this->executedQueries[] = $query;
        }
        return $this->lastResult;
    }

    /**
     * Fetch the first value from the first row of the select result
     * @param string $query
     * @param array|null $parameters
     * @return string|null
     */
    public function fetchOne(string $query, ?array $parameters = null): ?string
    {
        $result = $this->fetchAssocOne($query, $parameters);
        if ($result) {
            return reset($result);
        }
        return null;
    }

    /**
     * Fetch the first column of each row of the select result
     * The second column, if exist, will be the key of the resulting array
     * The result will just key/value pairs instead of multidimensional array
     * @param string $query
     * @param array|null $parameters
     * @return array
     */
    public function fetchColumn(string $query, ?array $parameters = null): array
    {
        $fetch = $this->fetchArray($query, $parameters);
        if ($fetch && isset($fetch[0][1])) {
            return ArrayUtils::map($fetch, 0, 1);
        }
        return ArrayUtils::map($fetch, 0);
    }

    /**
     * Fetch the complete result of a select as bare array (numeric indexes)
     * @param string $query
     * @param array|null $parameters
     * @param int|null $limit If set, than stop when the given limit is reached
     * @return array[]
     */
    public function fetchArray(
        string $query,
        ?array $parameters = null,
        ?int $limit = null
    ): array {
        $fetch = [];
        $result = $this->query($query, $parameters);
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $fetch[] = $row;
            if (is_int($limit) && $limit <= count($fetch)) {
                break;
            }
        }
        return $fetch;
    }

    /**
     * Fetch the complete result of a select as an array with column names as keys
     * @param string $query
     * @param array|null $parameters
     * @param string|null $valueAsArrayIndex Use the value if the given key as array index (instead of numeric index)
     * @param int|null $limit If set, than stop when the given limit is reached
     * @return array[]
     */
    public function fetchAssoc(
        string $query,
        ?array $parameters = null,
        ?string $valueAsArrayIndex = null,
        ?int $limit = null
    ): array {
        $fetch = [];
        $result = $this->query($query, $parameters);
        while ($row = $result->fetch_assoc()) {
            if (is_string($valueAsArrayIndex)) {
                if (!isset($row[$valueAsArrayIndex])) {
                    throw new Exception(
                        "Field '$valueAsArrayIndex' does not exist in SQL Result or is null",
                        ErrorCode::MYSQL_FETCH_ASSOC_INDEX_MISSING
                    );
                }
                $fetch[$row[$valueAsArrayIndex]] = $row;
            } else {
                $fetch[] = $row;
            }
            if (is_int($limit) && $limit <= count($fetch)) {
                break;
            }
        }
        return $fetch;
    }

    /**
     * Fetch the first row of a select as an array with column names as keys
     * @param string $query
     * @param array|null $parameters
     * @return array|null
     */
    public function fetchAssocOne(string $query, ?array $parameters = null): ?array
    {
        $arr = $this->fetchAssoc($query, $parameters, null, 1);
        if ($arr) {
            return $arr[0];
        }
        return null;
    }

    /**
     * Get last insert id from last insert query
     * @return int
     */
    public function getLastInsertId(): int
    {
        return (int)mysqli_insert_id($this->mysqli);
    }

    /**
     * Get affected rows of last query
     * @return int
     */
    public function getAffectedRows(): int
    {
        return (int)mysqli_affected_rows($this->mysqli);
    }

    /**
     * Replace parameter placeholders in qiven array
     * @param string $str Placeholders are written in {} brackets
     * @param array|null $parameters
     * @return string
     */
    public function replaceParameters(string $str, ?array $parameters = null): string
    {
        if (!is_array($parameters)) {
            return $str;
        }
        foreach ($parameters as $key => $value) {
            $str = str_replace('{' . $key . '}', $this->escapeValue($value), $str);
        }
        return $str;
    }
}