<?php

use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\Utils\Zip;

const FRAMELIX_MODULE = "Framelix";
require_once __DIR__ . "/../public/index.php";

$packageJson = JsonUtils::readFromFile(__DIR__ . "/../../../package.json");
$builtInModules = $packageJson['framelix']['builtInModules'];

$ignoreModuleFiles = [
    "^/.(git|svn|idea)",
    "^/config/config-editable.php$",
    "^/(dev|js|nodejs|node_modules|scss|tests|tmp)",
    "^/package-lock\.json",
];

$root = FileUtils::normalizePath(__DIR__ . "/../../..");
$filelist = [];
$arr = [
    "logs" => $root . "/logs",
    "modules" => $root . "/modules",
    ".htaccess" => $root . "/.htaccess",
    "index.php" => $root . "/index.php",
    "package.json" => $root . "/package.json"
];
$filelist = array_keys($arr);

foreach ($builtInModules as $module) {
    $shell = Shell::prepare("php {*}", [__DIR__ . "/create-module-package.php", $module])->execute();
    $zipFile = $shell->output[0];
    $arr["modules/$module.zip"] = $zipFile;
}
$filelistFile = __DIR__ . "/../tmp/filelist.json";
JsonUtils::writeToFile($filelistFile, $filelist);
$arr["filelist.json"] = $filelistFile;
Zip::createZip(__DIR__ . "/dist/release-" . $packageJson['version'] . ".zip", $arr);