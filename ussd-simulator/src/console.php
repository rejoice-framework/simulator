<?php
define('DEFAULT_IP', '127.0.0.1');
define('DEFAULT_PORT', '8000');
// date_default_timezone_set(env('TIME_ZONE'));

parse_str(implode('&', array_slice($_SERVER['argv'], 1)), $_GET);

function run_simulator($ip = '127.0.0.1', $port = '8000')
{
    $simulator_path = './vendor/prinx/ussd-simulator/src';
    if (file_exists(!$simulator_path)) {
        echo 'Simulator not found. Use `composer require prinx/ussd-simulator` to install it.';
        return;
    }

    // echo colorConsole('Server start at ' . date('d M Y, h : m : s'), ['fg' => 'green']) . ". Visit " . colorConsole('http://' . $ip . ':' . $port, ['fg' => 'green']) . "\nPress Ctrl+C to exit.\n";

    echo colorConsole('Server started at http://' . $ip . ':' . $port, ['fg' => 'green']) . "\nPress Ctrl+C to exit.\n";

    passthru('php -S ' . $ip . ':' . $port . ' -t ' . $simulator_path);
    exit;
}

function ask_ip()
{
    $res = trim(readConsoleLine("Enter the ip [" . DEFAULT_IP . "]: "));
    return $res ? $res : DEFAULT_IP;
}

function ask_port()
{
    $res = readConsoleLine("Enter the port [" . DEFAULT_PORT . "]: ");
    return $res ? $res : DEFAULT_PORT;
}

if ($_SERVER['argc'] > 2) {
    if ($_SERVER['argv'][2] === 'run') {
        run_simulator();
    }
} else {
    // $msg = "\nWhat can I do for you?\nSelect an option\n1. Run the simulator?\n\nResponse: ";

    // if (readConsoleLine($msg) === '1') {
    $ip = ask_ip();
    $port = ask_port();
    run_simulator($ip, $port);
    // }

    // exit("Goodbye\n\n");
}
