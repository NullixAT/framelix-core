<?php

use Framelix\Framelix\Framelix;
use Framelix\Framelix\View;

define("FRAMELIX_APP_ROOT", str_replace("\\", "/", dirname(__DIR__, 3)));

require __DIR__ . "/../src/Framelix.php";
Framelix::init();

if (!\Framelix\Framelix\Network\Request::isCli()) {
    View::loadViewForCurrentUrl();
}