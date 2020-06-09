<?php
namespace Prinx\Simulator\Libs;

class Log
{
    protected $file = '';
    protected $cache = '';

    public function __construct($file = 'simulator.log')
    {
        $this->file = realpath(__DIR__ . '/../storage/logs/') . '/' . $file;
        $this->cache = realpath(__DIR__ . '/../storage/cache/') . '/request-count.cache';

        if (!file_exists($this->cache)) {
            file_put_contents($this->cache, 0);
        }
    }

    public function info(
        $message,
        $file = 'simulator.log',
        $flag = FILE_APPEND
    ) {
        $num = intval(file_get_contents($this->cache)) + 1;

        $to_log = is_string($message) ? trim($to_log) : print_r($message, true);
        $to_log = '#' . $num . ' [' . date("D, d m Y, H:i:s") . "]\n" . $to_log . "\n\n";

        file_put_contents($this->file, $to_log, $flag);
        file_put_contents($this->cache, $num);
    }

    public function clear()
    {
        file_put_contents($this->file, '');
        file_put_contents($this->cache, 0);
    }
}
