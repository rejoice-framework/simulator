<?php
namespace Prinx\Simulator\Libs;

class Response
{
    public function __construct(array $response)
    {
        $this->data = $response;
    }

    public function send()
    {
        if ($this->data['SUCCESS']) {
            echo ($this->data['data']);
        } else {
            echo ("Response: " . $this->data['data'] . "<br><br>Error: " . $this->data['error']);
        }
    }

    public function data()
    {
        return $this->data;
    }
}
