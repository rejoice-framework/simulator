<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Simulator\Libs;

use function Prinx\Dotenv\env;
use PDO;
use Prinx\Notify\Log;
use Prinx\Utils\DB;
use Prinx\Utils\HTTP;
use Rejoice\Simulator\Libs\Request;
use Rejoice\Simulator\Libs\Response;

class Simulator
{
    protected $request = null;
    protected $endpoint = '';
    protected $payload = [];
    protected static $index = __DIR__.'/../../';

    public static function serve($ip = '127.0.0.1', $port = '8000')
    {
        passthru('php -S '.$ip.':'.$port.' -t "'.static::$index.'"', $return);
    }

    public static function groupUssdBy(string $column, array $ussds)
    {
        $columns = ['id', 'app_name', 'network', 'code', 'url'];

        if (!in_array($column, $columns)) {
            return [];
        }

        $grouped = [];
        foreach ($ussds as $ussd) {
            if (!isset($grouped[$ussd[$column]])) {
                $grouped[$ussd[$column]] = [];
            }

            $group = [];
            foreach ($columns as $value) {
                if ($value !== $column) {
                    $group[$value] = $ussd[$value];
                }
            }

            $grouped[$ussd[$column]][] = $group;
        }

        return $grouped;
    }

    public static function retrieveSavedUssdEndpoints()
    {
        $params = [
            'driver'   => env('USSD_ENDPOINT_DRIVER', 'mysql'),
            'host'     => env('USSD_ENDPOINT_HOST', 'localhost'),
            'port'     => env('USSD_ENDPOINT_PORT', 3306),
            'dbname'   => env('USSD_ENDPOINT_DB', ''),
            'user'     => env('USSD_ENDPOINT_DB_USER', ''),
            'password' => env('USSD_ENDPOINT_DB_PASS', ''),
        ];

        try {
            $db = DB::load($params);
        } catch (\Throwable $th) {
            return [];
        }

        $ussdTable = env('USSD_ENDPOINT_DB_TABLE', '');
        $numUssdEnpointsToRetrieve = env('USSD_ENDPOINT_NUM_TO_RETRIEVE', 300);

        $stmt = $db->prepare("SELECT * FROM `$ussdTable` ORDER BY id DESC LIMIT :to_retrieve");
        $stmt->bindParam('to_retrieve', $numUssdEnpointsToRetrieve, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * Capture the request coming from the simulator interface, send the
     * request to the application and return the response.
     *
     * @return Response
     */
    public function simulate()
    {
        $this->captureIncomingRequest();
        $response = $this->callUssd();
        $this->log($response);

        return $response;
    }

    /**
     * Capture request from the simulator interface.
     *
     * @return void
     */
    public function captureIncomingRequest()
    {
        try {
            $this->request = new Request;
        } catch (\Throwable $th) {
            exit(json_encode([
                'error'   => $th->getMessage(),
                'SUCCESS' => false,
            ]));
        }

        $this->payload = $this->request->data();
        $this->endpoint = urldecode($this->payload['endpoint']);
        unset($this->payload['endpoint']);
    }

    /**
     * Send the HTTP request to the USSD application and return the response.
     *
     * @return Response
     */
    public function callUssd()
    {
        return new Response(HTTP::post($this->payload, $this->endpoint));
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload(array $payload)
    {
        $this->payload = $payload;
    }

    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Log the response if response cannot be parse to JSON.
     *
     *
     *
     * @param  Response $response
     * @return void
     */
    public function log(Response $response)
    {
        if (!json_decode($response->data()['data'])) {
            $dir = realpath(__DIR__.'/../');
            $file = $dir.'/storage/logs/simulator.log';
            $cache = $dir.'/storage/cache/request-count.cache';
            $logger = new Log($file, $cache);
            $logger->warning($response->data());
        }
    }
}
