<?php

require_once __DIR__.'/../../../../../autoload.php';

use Rejoice\Simulator\Libs\Simulator;

$simulator = new Simulator;
$response = $simulator->simulate();
$response->send();
