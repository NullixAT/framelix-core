<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Config;

use function escapeshellarg;
use function exec;
use function implode;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Shell cmd execution
 */
class Shell
{
    /**
     * Command line interface color control codes to html color codes
     * @var array
     */
    public static array $cliColorCodes = [
        // foreground/text colors
        "30" => "color: black;", // Black
        "90" => "color: lightgrey;", // Dark Grey
        "31" => "color: red;", // Red
        "91" => "color: indianred;", // Light Red
        "32" => "color: green;", // Green
        "92" => "color: lime;", // Light Green
        "33" => "color: brown;", // Brown
        "93" => "color: sandybrown;", // Yellow
        "34" => "color: deepskyblue;", // Blue
        "94" => "color: lightblue;", // Light Blue
        "35" => "color: magenta;", // Magenta
        "95" => "color: pink;", // Light Magenta
        "36" => "color: cyan;", // Cyan
        "96" => "color: lightcyan;", // Light Cyan
        "37" => "color: lightcyan", // Light Grey
        "97" => "color: white;", // White
        "0" => "", // reset color
        "39" => "", // reset color
        // background colors
        "40" => "background-color: black;", // Black
        "41" => "background-color: red;", // Red
        "42" => "background-color: green;", // Green
        "43" => "background-color: yellow;", // Yellow
        "44" => "background-color: blue;", // Blue
        "45" => "background-color: magenta;", // Magenta
        "46" => "background-color: cyan;", // Cyan
        "47" => "background-color: lightgrey;", // Light Grey
    ];

    /**
     * The executable programm command line
     * @var string
     */
    public string $cmd = "";

    /**
     * The parameters to replace in $cmd
     * @var array
     */
    public array $params = [];

    /**
     * The return status of the execution, 0 means OK
     * @var int
     */
    public int $status = -1;

    /**
     * The output as string array where each line is an entry
     * @var array
     */
    public array $output = [];

    /**
     * Given a CLI output to correct escaped html output, it will decode command line colors into html <span> colors
     * @param array|string $output
     * @param bool $nl2br convertNewLines into <br/>
     * @return string
     */
    public static function convertCliOutputToHtml(array|string $output, bool $nl2br): string
    {
        $output = is_array($output) ? implode("\n", $output) : $output;
        $output = HtmlUtils::escape($output, $nl2br);
        $parts = preg_split("~\e\[([0-9;]+)m~s", $output, flags: PREG_SPLIT_DELIM_CAPTURE);
        $newOutput = '';
        for ($i = 0; $i < count($parts); $i++) {
            if ($i % 2 !== 0) {
                if ($i > 1) {
                    $newOutput .= '</span>';
                }
                $codes = explode(";", $parts[$i]);
                $newOutput .= '<span style="';
                foreach ($codes as $code) {
                    if (isset(self::$cliColorCodes[$code])) {
                        $newOutput .= self::$cliColorCodes[$code] . "; ";
                    }
                }
                $newOutput .= '">';
                continue;
            }
            $newOutput .= $parts[$i];
        }
        return $newOutput;
    }

    /**
     * Prepare a shell command
     * @param string $cmd Example: mysql --host={host} -u {user} {db} < {file}
     *      If the cmd ends with {*} than all $params will be appended automatically and you not need to use {placeholders}
     * @param array $params The parameters to replace in $cmd
     * @return Shell
     */
    public static function prepare(
        string $cmd,
        array $params = []
    ): Shell {
        $aliases = Config::get('shellAliases');
        if ($aliases) {
            foreach ($aliases as $alias => $path) {
                if (str_starts_with($cmd, $alias . " ")) {
                    $cmd = $path . " " . substr($cmd, strlen($alias) + 1);
                }
            }
        }
        $shell = new self();
        $paramsEscaped = [];
        foreach ($params as $key => $value) {
            $cmd = str_replace('{' . $key . '}', escapeshellarg($value), $cmd);
            $paramsEscaped[] = escapeshellarg($value);
        }
        if (str_ends_with($cmd, "{*}") && $paramsEscaped) {
            $cmd = substr($cmd, 0, -3);
            $cmd .= implode(" ", $paramsEscaped);
        }
        // 2>&1 pipes stderr to stdout to catch all to output
        $shell->cmd = "2>&1 " . $cmd;
        return $shell;
    }

    /**
     * Execute the shell command
     * @return self
     */
    public function execute(): self
    {
        exec($this->cmd, $this->output, $this->status);
        return $this;
    }
}
