<?php
if (php_sapi_name() !== 'cli' || !isset($_SERVER['argv'][1])) {
    exit;
}

/**
 * Get the newest child folder in given directory
 * @param string $folder
 * @return string|null
 */
function getNewestChildFolderPath(string $folder): ?string
{
    $files = scandir($folder);
    $newestFolderPath = null;
    foreach ($files as $file) {
        if ($file === "." || $file === "..") {
            continue;
        }
        $path = $folder . "/" . $file;
        if (is_dir($path) && !str_ends_with($file, ".plugins") && (!$newestFolderPath || filemtime($path) > filemtime($newestFolderPath))) {
            $newestFolderPath = $path;
        }
    }
    return $newestFolderPath;
}

$appDataRoot = dirname(system("echo %appdata%"));
$phpStormFolder = $appDataRoot . "/Local/JetBrains/Toolbox/apps/PhpStorm";
$phpStormExe = null;
if (is_dir($phpStormFolder)) {
    $phpStormFolder = getNewestChildFolderPath($phpStormFolder);
    if ($phpStormFolder) {
        $phpStormFolder = getNewestChildFolderPath($phpStormFolder);
        if ($phpStormFolder) {
            $phpStormExe = $phpStormFolder . "/bin/phpstorm64.exe";
        }
    }
}
if (!$phpStormExe) {
    $phpStormFolder = "C:/program files/jetbrains";
    $phpStormFolder = getNewestChildFolderPath($phpStormFolder);
    if ($phpStormFolder) {
        $phpStormExe = $phpStormFolder . "/bin/phpstorm64.exe";
    }
}
if (!$phpStormExe || !file_exists($phpStormExe)) {
    return;
}
$url = urldecode($_SERVER['argv'][1]);
parse_str(substr($url, strpos($url, "?") + 1), $params);
if (!isset($params['line'])) {
    $spl = explode(":", $params['file']);
    if (count($spl) > 1) {
        $line = end($spl);
        if (is_numeric($line)) {
            $params['line'] = $line;
            array_pop($spl);
            $params['file'] = implode(":", $spl);
        }
    }
}
exec('"' . $phpStormExe . '" --line ' . $params['line'] . " " . escapeshellarg($params['file']));