<?php

use Framelix\Framelix\Config;

const FRAMELIX_MODULE = "Framelix";
include __DIR__ . "/../public/index.php";

// override the default database in case if something goes wrong and the tests would run on the default db rather than the test db
Config::set('database[default]', null);
// disable system event log, it should be tested explicitely
Config::set('systemEventLog', null);