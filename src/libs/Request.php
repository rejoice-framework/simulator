<?php
namespace Prinx\Simulator\Libs;

class Request
{
    protected $requiredParams = [
        'endpoint',
        'msisdn',
        'network',
        'ussdString',
        'sessionID',
        'ussdServiceOp',
    ];

    public function __construct()
    {
        $this->data = $_POST;
        $this->checkRequiredParams($this->data);
    }

    public function checkRequiredParams($params)
    {
        foreach ($this->requiredParams as $param) {
            if (!isset($params[$param])) {
                throw new \Exception('Parameter"' . $param . '" is required');
            }
        }
    }

    public function data()
    {
        return $this->data;
    }
}
