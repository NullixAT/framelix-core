<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Utils\NumberUtils;
use JetBrains\PhpStorm\ExpectedValues;

use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function substr_count;
use function trim;

/**
 * Lazy Search Condition to provide a lazy search with ease
 */
class LazySearchCondition
{
    /**
     * If set, this condition is always prepend on the output of getPreparedCondition()
     * @var string|null
     */
    public ?string $prependFixedCondition = null;

    /**
     * Available columns
     * @var array
     */
    public array $columns = [];

    /**
     * Add a column to be able to search for in the condition
     * @param string $dbPropertyName The property name to use in the sql condition
     * @param string $frontendPropertyName The property that can be intereded in the frontend by the user, to select the specific column for the query part
     * @param string|null $label The label for the user to have a readable name, is used in combination with quick search
     * @param string $type The column type which is the internal php type (int,float,etc...) or a valid class name
     */
    public function addColumn(
        string $dbPropertyName,
        string $frontendPropertyName,
        ?string $label = null,
        #[ExpectedValues(["bool", "int", "float", "string", Date::class, DateTime::class])]
        string $type = "string"
    ): void {
        $this->columns[$dbPropertyName] = [
            'dbPropertyName' => $dbPropertyName,
            'frontendPropertyName' => $frontendPropertyName,
            'label' => $label,
            'type' => $type,
        ];
    }

    /**
     * Get prepared condition based on the columns
     * @param Mysql $db The database connection
     * @param string $userSearchQuery
     * @return string
     */
    public function getPreparedCondition(Mysql $db, string $userSearchQuery): string
    {
        $condition = (string)$this->prependFixedCondition;
        // this wildcard search for everything
        if ($userSearchQuery === "*" || $userSearchQuery === "**") {
            return strlen($condition) ? $condition : "1";
        }
        $userSearchQuery = trim(preg_replace("~\s{2,}~", " ", $userSearchQuery));
        $matchGroupsSearch = [];
        $matchGroupsReplace = [];
        // find all full sentence search strings
        while (substr_count($userSearchQuery, '"') >= 2) {
            preg_match("~\"(.*?)\"~", $userSearchQuery, $matchGroup);
            if ($matchGroup) {
                $key = count($matchGroupsSearch);
                $matchGroupsSearch[$key] = '{#' . $key . '#}';
                $matchGroupsReplace[$key] = $matchGroup[1];
                $userSearchQuery = str_replace($matchGroup[0], $matchGroupsSearch[$key], $userSearchQuery);
            }
        }
        $orParts = explode("|", $userSearchQuery);
        $conditionOrParts = [];
        foreach ($orParts as $orPart) {
            $conditionMain = [];
            $userSearchQueryParts = explode(" ", trim($orPart));
            foreach ($userSearchQueryParts as $userSearchQueryPart) {
                $userSearchQueryPart = trim($userSearchQueryPart);
                if (!strlen($userSearchQueryPart)) {
                    continue;
                }
                $userSearchQueryPart = str_replace($matchGroupsSearch, $matchGroupsReplace, $userSearchQueryPart);

                $columnsUse = $this->columns;
                // if we have a column name comparator in query string only search in this
                foreach ($this->columns as $columnRow) {
                    if (preg_match(
                        "~^" . preg_quote($columnRow['frontendPropertyName'], "~") . "([!=><\~]+.*)~i",
                        $userSearchQueryPart,
                        $match
                    )) {
                        $userSearchQueryPart = $match[1];
                        $columnsUse = [$columnRow];
                    }
                }

                $conditionParts = [];
                foreach ($columnsUse as $columnRow) {
                    $columnName = str_contains(
                        $columnRow['dbPropertyName'],
                        "."
                    ) ? $columnRow['dbPropertyName'] : "`" . $columnRow['dbPropertyName'] . "`";
                    $userSearchQueryPartForColumn = $userSearchQueryPart;
                    if ($columnRow['type'] === 'bool') {
                        $compareOperators = "=";
                        if (str_starts_with($userSearchQueryPartForColumn, "=")) {
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 1);
                        } elseif (str_starts_with($userSearchQueryPartForColumn, "!=")) {
                            $compareOperators = "!=";
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 2);
                        }
                        if ($userSearchQueryPartForColumn === "1" || strtolower(
                                $userSearchQueryPartForColumn
                            ) === strtolower(Lang::get('__framelix_yes__'))) {
                            $userSearchQueryPartForColumn = "1";
                        } elseif ($userSearchQueryPartForColumn === "0" || strtolower(
                                $userSearchQueryPartForColumn
                            ) === strtolower(Lang::get('__framelix_no__'))) {
                            $userSearchQueryPartForColumn = "0";
                        } else {
                            // skip of the query is not a valid bool value
                            continue;
                        }
                        $userSearchQueryPartForColumn = $userSearchQueryPartForColumn === "0" ? 0 : 1;
                    } elseif ($columnRow['type'] === 'int' || $columnRow['type'] === 'float' || $columnRow['type'] === DateTime::class || $columnRow['type'] === Date::class) {
                        $compareOperators = "=";
                        if (str_starts_with($userSearchQueryPartForColumn, ">=")) {
                            $compareOperators = ">=";
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 2);
                        } elseif (str_starts_with($userSearchQueryPartForColumn, ">")) {
                            $compareOperators = ">";
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 1);
                        } elseif (str_starts_with($userSearchQueryPartForColumn, "<=")) {
                            $compareOperators = "<=";
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 2);
                        } elseif (str_starts_with($userSearchQueryPartForColumn, "<")) {
                            $compareOperators = "<";
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 1);
                        } elseif (str_starts_with($userSearchQueryPartForColumn, "=")) {
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 1);
                        } elseif (str_starts_with($userSearchQueryPartForColumn, "!=")) {
                            $compareOperators = "!=";
                            $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 2);
                        }
                        if ($columnRow['type'] === DateTime::class) {
                            $dateTime = DateTime::create($userSearchQueryPartForColumn)?->getDbValue();
                            if ($dateTime && strlen($userSearchQueryPartForColumn) <= 10) {
                                $dateTime = substr($dateTime, 0, 10);
                            }
                            $userSearchQueryPartForColumn = $dateTime;
                            if (!$userSearchQueryPartForColumn) {
                                continue;
                            }
                        } elseif ($columnRow['type'] === Date::class) {
                            $userSearchQueryPartForColumn = Date::create($userSearchQueryPartForColumn)?->getDbValue();
                            if (!$userSearchQueryPartForColumn) {
                                continue;
                            }
                        } elseif ($columnRow['type'] === 'float') {
                            // check if given string has any not supported numeric char, if so, skip
                            if (preg_match("~[^-0-9,.]~", $userSearchQueryPartForColumn) || substr_count(
                                    $userSearchQueryPartForColumn,
                                    "."
                                ) > 1) {
                                continue;
                            }
                            $userSearchQueryPartForColumn = NumberUtils::toFloat($userSearchQueryPartForColumn);
                        } elseif ($columnRow['type'] === 'int') {
                            // check if given string has any not supported numeric char, if so, skip
                            if (!!preg_match("~[^-0-9]~", $userSearchQueryPartForColumn)) {
                                continue;
                            }
                            $userSearchQueryPartForColumn = (int)$userSearchQueryPartForColumn;
                        }
                    } elseif (str_starts_with($userSearchQueryPartForColumn, "=")) {
                        $compareOperators = "=";
                        $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 1);
                    } elseif (str_starts_with($userSearchQueryPartForColumn, "!=")) {
                        $compareOperators = "!=";
                        $userSearchQueryPartForColumn = substr($userSearchQueryPartForColumn, 2);
                    } elseif (str_starts_with($userSearchQueryPartForColumn, "!~")) {
                        $compareOperators = "NOT LIKE";
                        $userSearchQueryPartForColumn = "%" . substr($userSearchQueryPartForColumn, 1) . "%";
                    } else {
                        $compareOperators = "LIKE";
                        $userSearchQueryPartForColumn = "%" . ltrim($userSearchQueryPartForColumn, "~") . "%";
                    }
                    $conditionPart = "$columnName $compareOperators " . $db->escapeValue($userSearchQueryPartForColumn);
                    $conditionParts[] = $conditionPart;
                }
                if ($conditionParts) {
                    $conditionMain[] = $this->partsToStr($conditionParts, "||");
                }
            }
            if ($conditionMain) {
                $conditionOrParts[] = $this->partsToStr($conditionMain, "&&");
            }
        }
        if (!$conditionOrParts && $condition === '') {
            return "0";
        }
        if ($condition !== '') {
            $condition .= " && ";
        }
        $condition .= $this->partsToStr($conditionOrParts, "||", true);
        return $condition;
    }

    /**
     * Turns parts array into string
     * @param array $parts
     * @param string $concat
     * @param bool $forceParentheses
     * @return string
     */
    private function partsToStr(array $parts, string $concat, bool $forceParentheses = false): string
    {
        if (!count($parts)) {
            return "0";
        }
        if (
            count($parts) > 1 ||
            ($forceParentheses && !str_starts_with($parts[0], "(") && !str_ends_with($parts[0], ")"))) {
            return "(" . implode(" $concat ", $parts) . ")";
        }
        return implode(" $concat ", $parts);
    }
}