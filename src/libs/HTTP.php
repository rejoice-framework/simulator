<?php

/*
 * This file is part of the PHPUtils package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Simulator\Libs;

/**
 * HTTP requests utility class
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class HTTP
{
    public static function post($postvars, $endpoint, $requestDescription = '')
    {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $endpoint);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postvars);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl_handle);
        $err = curl_error($curl_handle);
        curl_close($curl_handle);

        $response = [
            'SUCCESS' => true,
            'data' => $result,
        ];

        if ($err) {
            $description = '';

            if ($requestDescription) {
                $description = '<br/><span style="color:red;">ERROR POST REQUEST:</span> ' . $requestDescription . '<br/>';
            }

            $response = [
                'SUCCESS' => false,
                'error' => $description . $err,
            ];
        }

        return $response;
    }
}
