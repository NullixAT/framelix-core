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
                    $cmd = $path . " " . substr($cmd, strlen($alias));
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
