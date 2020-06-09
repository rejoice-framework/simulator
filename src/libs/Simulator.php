<?php
namespace Prinx\Simulator\Libs;

use Prinx\Simulator\Libs\HTTP;
use Prinx\Simulator\Libs\Request;
use Prinx\Simulator\Libs\Response;

class Simulator
{
    protected $request = null;
    protected $endpoint = '';
    protected $payload = [];

    public function simulate()
    {
        $this->captureIncomingRequest();
        return $this->callUssd();
    }

    public function captureIncomingRequest()
    {
        $this->request = new Request;

        $this->endpoint = urldecode($this->request->data()['endpoint']);
        $this->payload = $this->request->data();

        unset($this->payload['endpoint']);
    }

    public function callUssd()
    {
        return new Response(HTTP::post($this->payload, $this->endpoint));
    }
}
