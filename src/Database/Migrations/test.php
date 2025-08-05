<?php

include_once __DIR__ . '/../../../vendor/autoload.php';

$handler = new Proto\Database\Migrations\Guide();
$handler->run();