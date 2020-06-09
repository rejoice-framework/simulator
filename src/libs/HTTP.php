<?php
namespace Prinx\Simulator\Libs;

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
                $description = '<br><span style="color:red;">ERROR POST REQUEST:</span> ' . $request_description . '<br>';
            }

            $response['SUCCESS'] = false;
            $response['error'] = $description . $err;
        }

        return $response;
    }
}
