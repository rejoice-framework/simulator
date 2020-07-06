<?php
require_once './../../../vendor/autoload.php';

use Prinx\Simulator\Libs\Simulator;

$simulator = new Simulator;
$response = $simulator->simulate();
$response->send();
