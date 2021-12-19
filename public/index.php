<?php

use Framelix\Framelix\Framelix;
use Framelix\Framelix\View;

const FRAMELIX_MIN_PHP_VERSION = "8.1.0";
define("FRAMELIX_APP_ROOT", str_replace("\\", "/", dirname(__DIR__, 3)));

require __DIR__ . "/../src/Framelix.php";
Framelix::init();

if (!\Framelix\Framelix\Network\Request::isCli()) {
    View::loadViewForCurrentUrl();
}