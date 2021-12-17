<?php

$configExist = file_exists(__DIR__ . "/../../../config/config.php");
// only do this base checks when setup isn't done yet
if (!$configExist) {
    $minVersion = "8.1.0";
    if (version_compare(PHP_VERSION, $minVersion) < 0) {
        http_response_code(500);
        echo "This application requires at least PHP $minVersion";
        die();
    }

    $requiredExtensions = ['fileinfo', 'mbstring', 'mysqli', 'sockets', 'json', 'curl', 'simplexml', 'zip', 'openssl'];
    $missingExtensions = [];

    foreach ($requiredExtensions as $requiredExtension) {
        if (!extension_loaded($requiredExtension)) {
            $missingExtensions[$requiredExtension] = $requiredExtension;
        }
    }
    if ($missingExtensions) {
        http_response_code(500);
        echo "This application requires the following php extensions to be functional: " . implode(", ",
                $missingExtensions) . "<br/>Please add it to your php.ini configuration";
        die();
    }
}