#!/usr/bin/php
<?php
// this script provide some console scripts

if (php_sapi_name() !== 'cli' || !isset($_SERVER['argv'][0])) {
    echo "This script can only be called from command-line";
    exit;
}

ini_set("memory_limit", -1);
ini_set("max_execution_time", -1);


/**
 * Draw line
 * @param string $messages
 */
function drawLine(string $messages)
{
    echo $messages . "\n";
}

$argv = $_SERVER['argv'];
unset($argv[0]);


$actions = [];

// search active module
$folders = scandir(__DIR__ . "/..");
$activeModule = "Framelix";
foreach ($folders as $folder) {
    $configFile = __DIR__ . "/../$folder/config/config-editable.php";
    if (file_exists($configFile)) {
        $activeModule = $folder;
        break;
    }
}

define("FRAMELIX_MODULE", $activeModule);
require __DIR__ . "/public/index.php";

// fetch all available jobs
foreach (\Framelix\Framelix\Config::$loadedModules as $module) {
    $consoleClass = "\\Framelix\\$module\\Console";
    if (!class_exists($consoleClass)) {
        continue;
    }
    $reflectionClass = new ReflectionClass($consoleClass);
    foreach ($reflectionClass->getMethods() as $method) {
        if (!$method->isPublic() || !$method->isStatic()) {
            continue;
        }
        if ($method->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
            continue;
        }
        $name = $method->getName();
        $parsedComment = \Framelix\Framelix\Utils\PhpDocParser::parse($method->getDocComment());
        $description = trim(implode("\n", $parsedComment['description']));
        if (isset($actions[$name])) {
            echo "Warning: Console Job $name already exist and $module try to override it\n";
            continue;
        }
        $actions[$name] = [
            'description' => $description,
            'callable' => $consoleClass . "::$name"
        ];
    }
}

if (!$argv || !isset($actions[$argv[1] ?? ''])) {
    drawLine("");
    drawLine("Welcome to the Framelix console");
    drawLine("Following actions are available");
    drawLine("Call script with console.php {actionName} [optional parameters]");
    drawLine("");
    foreach ($actions as $action => $row) {
        drawLine($action . " => " . $row['description']);
    }
    drawLine("");
    return;
}

$activeModule = "Framelix";

drawLine("");
drawLine("Context: " . FRAMELIX_MODULE);
drawLine("Action: " . $argv[1]);
drawLine("");

$action = $actions[$argv[1]];
call_user_func_array($action['callable'], []);
drawLine("");