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
    public static function post(
        array $payload,
        string $endpoint,
        string $requestDescription = '',
        array $customCurlOptions = []
    ) {
        $defaultCurlOptions = [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => 'UTF-8',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 60,
        ];
        $curlOptions = array_replace_recursive(
            $defaultCurlOptions,
            $customCurlOptions
        );

        $curl_handle = curl_init();
        curl_setopt_array($curl_handle, $curlOptions);
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
