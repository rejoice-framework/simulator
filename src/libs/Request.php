<?php
namespace Prinx\Simulator\Libs;

class Request
{
    protected $required_params = [
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
        $this->validateParams($this->data);
    }

    public function validateParams($params)
    {
        foreach ($this->required_params as $param) {
            if (!isset($params[$param])) {
                exit('"' . $param . '" parameter is required');
            }
        }
    }

    public function data()
    {
        return $this->data;
    }
}
