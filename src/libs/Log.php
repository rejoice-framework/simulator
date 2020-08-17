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

/**
 * Default logger.
 *
 * Simply log into the application.
 */
class Log
{
    protected $file = '';
    protected $cache = '';
    protected $levels = [
        'debug',
        'info',
        'notice',
        'warning',
        'error',
        'critical',
        'alert',
        'emergency',
    ];

    public function __construct($file = '', $cache = '')
    {
        // File will be created on the first logging attempt
        $this->file = $file;
        $this->cache = $cache;

        if (!file_exists($this->cache)) {
            file_put_contents($this->cache, 0);
        }
    }

    public function debug($message, $flag = FILE_APPEND)
    {
        $this->log('debug', $message, $flag);
    }

    public function info($message, $flag = FILE_APPEND)
    {
        $this->log('info', $message, $flag);
    }

    public function notice($message, $flag = FILE_APPEND)
    {
        $this->log('notice', $message, $flag);
    }

    public function warning($message, $flag = FILE_APPEND)
    {
        $this->log('warning', $message, $flag);
    }

    public function error($message, $flag = FILE_APPEND)
    {
        $this->log('error', $message, $flag);
    }

    public function critical($message, $flag = FILE_APPEND)
    {
        $this->log('critical', $message, $flag);
    }

    public function alert($message, $flag = FILE_APPEND)
    {
        $this->log('alert', $message, $flag);
    }

    public function emergency($message, $flag = FILE_APPEND)
    {
        $this->log('emergency', $message, $flag);
    }

    /**
     * Log method for any level
     *
     * If $message is string, it will be logged as-is.
     * If message is an array, it will be converted to json
     * If message is an object AND implements the `__toString` method
     * it will be converted to string
     * Else, the message will be print with print_r.
     *
     * @param  string              $level
     * @param  string|array|object $message
     * @param  const               $flag
     * @throws \Exception
     * @return void
     */
    public function log(string $level, $message, $flag = FILE_APPEND)
    {
        if (!method_exists($this, $level)) {
            throw new \Exception('Log level `'.$level.'` not supported');
        }

        if (is_string($message)) {
            $toLog = trim($message);
        } elseif (is_array($message)) {
            $toLog = json_encode($message, JSON_PRETTY_PRINT);
        } elseif (is_object($message) && method_exists($message, '__toString')) {
            $toLog = strval($message);
        } else {
            $toLog = print_r($message, true);
        }

        $num = intval(file_get_contents($this->cache)) + 1;

        $toLog = '#'.$num.' ['.strtoupper($level).'] ['.date('D, d m Y, H:i:s')."]\n".$toLog."\n\n";

        file_put_contents($this->file, $toLog, $flag);
        file_put_contents($this->cache, $num);
    }

    public function clear()
    {
        file_put_contents($this->file, '');
        file_put_contents($this->cache, 0);
    }
}
