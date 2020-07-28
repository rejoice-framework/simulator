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
            $data = $this->data['data'];
            if (
                is_string($data) ||
                (is_object($data) && method_exists($data, '__toString'))
            ) {
                echo strval($data);
            } elseif (
                is_array($data) ||
                (is_object($data) && method_exists($data, 'jsonSerialize'))
            ) {
                echo json_encode($data);
            } else {
                print_r($data);
            }
        } else {
            echo "Response: " . $this->data['data'] . "<br><br>Error: " . $this->data['error'];
        }
    }

    public function data($key = null)
    {
        return $key ? $this->get($key) : $this->data;
    }

    public function isSuccess()
    {
        return is_array($this->data) && $this->data['SUCCESS'];
    }

    public function has($key)
    {
        return is_array($this->data) && isset($this->data[$key]);
    }

    public function get($key = null, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}
