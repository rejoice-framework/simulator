<?php

$hostAutoload = __DIR__.'/../../../../../autoload.php';
$localAutolaod = __DIR__.'/../../../vendor/autoload.php';

require_once file_exists($hostAutoload) ? $hostAutoload : $localAutolaod;
use Rejoice\Simulator\Libs\Simulator;

$simulator = new Simulator;
$response = $simulator->simulate();
$response->send();
