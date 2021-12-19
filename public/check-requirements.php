<?php
// check minimum requirements for framelix
const FRAMELIX_MIN_PHP_VERSION = "8.1.0";

if (version_compare(PHP_VERSION, FRAMELIX_MIN_PHP_VERSION) < 0) {
    http_response_code(500);
    echo "This application requires at least PHP " . FRAMELIX_MIN_PHP_VERSION;
    exit;
}

$requiredExtensions = [
    'exif',
    'fileinfo',
    'mbstring',
    'mysqli',
    'sockets',
    'json',
    'curl',
    'simplexml',
    'zip',
    'openssl'
];
$missingExtensions = [];

foreach ($requiredExtensions as $requiredExtension) {
    if (!extension_loaded($requiredExtension)) {
        $missingExtensions[$requiredExtension] = $requiredExtension;
    }
}
if ($missingExtensions) {
    http_response_code(500);
    echo "This application requires the following php extensions to be functional: " . implode(
            ", ",
            $missingExtensions
        ) . "<br/>Please add it to your php.ini configuration";
    exit;
}