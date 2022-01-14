<?php
// this script contains the initial installation script which just unpacks the release package that have been
// created with create-release-package.php

if (!file_exists(__DIR__ . "/check-requirements.php")) {
    echo "Missing check requirements file";
    exit;
}
/** @noinspection PhpIncludeInspection */
require __DIR__ . "/check-requirements.php";

$currentFiles = count(scandir(__DIR__));
$zipFile = __DIR__ . "/package.zip";
$outputDirectory = __DIR__;
$canInstall = file_exists($zipFile) && !file_exists(__DIR__ . "/index.php") && file_exists(__DIR__ . "/check-requirements.php");

if (($_GET['unpack'] ?? null) && $canInstall) {
    $zipArchive = new ZipArchive();
    $openResult = $zipArchive->open($zipFile, ZipArchive::RDONLY);
    if ($openResult !== true) {
        throw new Exception("Cannot open ZIP File '$zipFile' ($openResult)");
    }
    $zipArchive->extractTo($outputDirectory);
    $zipArchive->close();
    // delete install script and go ahead
    if (is_dir(__DIR__ . "/modules")) {
        // unpack all module zips
        $files = scandir(__DIR__ . "/modules");
        foreach ($files as $file) {
            if (str_ends_with($file, ".zip")) {
                $moduleName = substr($file, 0, -4);
                $moduleDirectory = __DIR__ . "/modules/$moduleName";
                if (!is_dir($moduleDirectory)) {
                    mkdir($moduleDirectory);
                }
                $moduleZipFile = __DIR__ . "/modules/$file";
                $zipArchive = new ZipArchive();
                $openResult = $zipArchive->open($moduleZipFile, ZipArchive::RDONLY);
                if ($openResult !== true) {
                    throw new Exception("Cannot open ZIP File '$zipFile' ($openResult)");
                }
                $zipArchive->extractTo($moduleDirectory);
                $zipArchive->close();
                unlink($moduleZipFile);
            }
        }
        unlink(__FILE__);
        unlink(__DIR__ . "/check-requirements.php");
        unlink($zipFile);
    }
    header("location: " . str_replace(["?unpack=1", "install.php"], "", $_SERVER['REQUEST_URI']));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>First Time Setup</title>
    <style>
      body {
        font-family: "Arial", sans-serif;
        font-size: 16px;
        line-height: 1.4;
        text-align: center;
        background: #f5f5f5;
        color: #333;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
      }
      .main {
        margin: 0 auto;
        max-width: 800px;
        background: white;
        padding: 40px;
        box-shadow: rgba(0, 0, 0, 0.1) 0 0 30px;
        border-radius: 40px;
      }
    </style>
</head>
<body>
<div class="main">
    <h1>First Time Setup</h1>
    <?php
    if (!file_exists($zipFile)) {
        echo '"package.zip" does not exist in this directory';
    } elseif (!$canInstall) {
        ?>
        Cannot start installation. Some required files are missing.
        <?php
    } else {
        ?>
        <a href="?unpack=1">Click here to start setup</a>
        <?php
    }
    ?>
</div>
</body>
</html>