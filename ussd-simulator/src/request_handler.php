<?php
namespace Prinx\Simulator;

require_once __DIR__ . '/../../../autoload.php';
require_once __DIR__ . '/../../../../app/Helpers/helpers.php';

class HTTP
{
    public static function post($postvars, $endpoint, $request_description = '')
    {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $endpoint);
        curl_setopt($curl_handle, CURLOPT_POST, true);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postvars);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_VERBOSE, true);

        $result = curl_exec($curl_handle);
        $err = curl_error($curl_handle);

        curl_close($curl_handle);

        $response = [
            'SUCCESS' => true,
            'data' => $result,
        ];

        if ($err) {
            $description = '';

            if ($request_description) {
                $description = '<br/><span style="color:red;">ERROR POST REQUEST:</span> ' . $request_description . '<br/>';
            }

            $response['SUCCESS'] = false;
            $response['error'] = $description . $err;
        }

        return $response;
    }
}

$required_params = [
    'endpoint',
    'msisdn',
    'network',
    'ussdString',
    'sessionID',
    'ussdServiceOp',
];

foreach ($required_params as $param) {
    if (!isset($_POST[$param])) {
        exit('"' . $param . '" parameter is required');
    }
}

$endpoint = urldecode($_POST['endpoint']);

unset($_POST['endpoint']);

$response = HTTP::post($_POST, $endpoint);
logMessage(print_r($response, true));
if ($response['SUCCESS']) {
    echo ($response['data']);
} else {
    echo ("Response: " . $response['data'] . "<br><br>Error: " . $response['error']);
}
