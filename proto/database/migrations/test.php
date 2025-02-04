<?php

include_once __DIR__ . '/../../autoload.php';

$handler = new Proto\Database\Migrations\Guide();
$handler->run();