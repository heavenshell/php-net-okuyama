<?php
/**
 * Socket-based adapter for \Net\Okuyama.
 *
 * PHP version 5.3
 *
 * Copyright (c) 2011 Shinya Ohyanagi, All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Shinya Ohyanagi nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @use       \Net\Okuyama
 * @use       \Net\Okuyama\Adapter
 * @use       \Net\Okuyama\Exception
 * @category  \Net
 * @package   \Net\Okuyama
 * @version   $id$
 * @copyright (c) 2011 Shinya Ohyanagi
 * @author    Shinya Ohyanagi <sohyanagi@gmail.com>
 * @license   New BSD License
 */

namespace Net\Okuyama\Adapter;
use Net\Okuyama,
    Net\Okuyama\Adapter,
    Net\Okuyama\Exception;

/**
 * Socket.
 *
 * @use       Net\Okuyama
 * @use       \Net\Okuyama\Adapter
 * @use       \Net\Okuyama\Exception
 * @category  \Net
 * @package   \Net\Okuyama
 * @version   $id$
 * @copyright (c) 2011 Shinya Ohyanagi
 * @author    Shinya Ohyanagi <sohyanagi@gmail.com>
 * @license   New BSD License
 */
class Socket implements Adapter
{
    /**
     * Version.
     */
    const VERSION = '0.0.1';

    /**
     * Data delimiter.
     */
    const DATA_DELIMITER = ',';

    /**
     * Tag delimiter.
     */
    const TAG_DELIMITER = ':';

    /**
     * Byte data delimiter.
     */
    const BYTE_DATA_DELIMITER = ':#:';

    /**
     * Alternative blank string.
     */
    const BLANK_STRING = '(B)';

    /**
     * Transaction code.
     */
    const TRANSACTION_CODE = '0';

    /**
     * Initialize Okuyama.
     */
    const ID_INIT = '0';

    /**
     * Set data.
     */
    const ID_SET = '1';

    /**
     * Get data.
     */
    const ID_GET = '2';

    /**
     * Set tag data.
     */
    const ID_TAG_SET = '3';

    /**
     *  Get tag data.
     */
    const ID_TAG_GET = '4';

    /**
     * Remove data.
     */
    const ID_REMOVE = '5';

    /**
     * Add data(not override).
     */
    const ID_ADD = '6';

    /**
     * Socket resouce.
     *
     * @var    mixed
     * @access private
     */
    private $_socket = null;

    /**
     * Error no when fsockopen failed.
     *
     * @var    mixed
     * @access private
     */
    private $_errorno = null;

    /**
     * Error message when fsockopen failed.
     *
     * @var mixed
     * @access private
     */
    private $_errormsg = null;

    /**
     * Connection timeout.
     *
     * @var    mixed
     * @access private
     */
    private $_timeout = 10;

    /**
     * Connected host and port.
     *
     * @var mixed
     * @access private
     */
    private $_connectedHost = null;

    /**
     * Binary data per size.
     *
     * @var    int
     * @access private
     */
    private $_size = 2560;

    /**
     * Max data size to save.
     *
     * @var    int
     * @access private
     */
    private $_maxSize = 2560;

    /**
     * Raw data.
     *
     * @var    mixed
     * @access private
     */
    private $_rawData = null;

    /**
     * Constructor.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Get raw data.
     *
     * <pre>
     *   Raw means Okuyama original format.
     * </pre>
     *
     * @access public
     * @return mixed Raw data
     */
    public function getRawData()
    {
        return $this->_rawData;
    }

    /**
     * Get error message.
     *
     * @access public
     * @return mixed Error message
     */
    public function getErrorMessage()
    {
        return $this->_errormsg;
    }

    /**
     * Set config options.
     *
     * @param  array $args Config options
     * @access public
     * @return \Net\Okuyama\Adapter\Socket Fluent interface
     */
    public function setConfig(array $args)
    {
        $this->_timeout = isset($args['timeout']) ? $args['timeout'] : 10;

        return $this;
    }

    /**
     * Connect to socket.
     *
     * @param  mixed $host Host name
     * @param  mixed $port Port number
     * @access public
     * @return mixed Resouce handler or false
     */
    public function connect($host, $port)
    {
        if (false === ($socket = @fsockopen(
                $host, $port, $this->_errorno,
                $this->_errormsg, $this->_timeout))) {
            return false;
        }

        $this->_socket = $socket;

        // Initialize connection.
        // Attempt to send initialize save size to Okuyama.
        $command  = '0' . self::DATA_DELIMITER . "\n";
        $response = $this->_parse($this->send($command)->response(), self::ID_INIT);
        $result   = array($response[1], $response[2]);
        $this->_rawData = $result;
        if ($response[1] === 'true') {
            $this->_size    = $response[2];
            $this->_maxSize = $response[2];
        } else {
            return false;
        }

        $this->_connectedHost = $host . ':' . $port;

        return $socket;
    }

    /**
     * Connect to host by randam.
     *
     * @param  array $hosts
     * @access public
     * @throws \Net\Okuyama\Exception Connection refused
     * @return bool true: Connection sucess
     */
    public function autoConnect(array $hosts)
    {
        $list = $hosts;
        if (count($hosts) > 1) {
            shuffle($list);
        }

        $socket = null;
        while (count($list) > 0) {
            $data = array_shift($list);
            if ($this->_validateHostFormat($data) === false) {
                continue;
            }
            list($host, $port) = explode(':', $data);
            $socket = $this->connect($host, $port);
            if ($socket !== false) {
                break;
            }
        }

        if ($socket === null) {
            throw new Exception('All hosts connection refused.');
        }

        return true;
    }

    /**
     * Close connection.
     *
     * @access public
     * @throws \Net\Okuyama\Exception Connection already closed
     * @return bool true: Connection close sucess
     */
    public function close()
    {
        if ($this->_socket === null || fclose($this->_socket) === false) {
            throw new Exception('Connection already closed.');
        }
        $this->_socket = null;
        return true;
    }

    /**
     * Is socket connected?
     *
     * @access public
     * @return bool true: Connected, false: Not connected
     */
    public function isConnected()
    {
        if ($this->_socket === null) {
            return false;
        }

        return true;
    }

    /**
     * Get data.
     *
     * @param  mixed $key Key
     * @param  mixed $tag Tag
     * @access public
     * @throws \Net\Okuyama\Adapter\Socket Server returns error
     * @return mixed null or value
     */
    public function get($key)
    {
        $command  = self::ID_GET . self::DATA_DELIMITER . base64_encode($key) . "\n";
        $response = $this->_parse($this->send($command)->response(), self::ID_GET);
        $result = array($response[1]);
        if ($response[1] === 'true') {
            if ($response[2] === self::BLANK_STRING) {
                $result[] = '';
            } else {
                $result[] = base64_decode($response[2]);
            }
        } else if ($response[1] === 'false') {
            $result[] = null;
        } else if ($response[1] === 'error') {
            $result[] = null;
        } else {
            throw new Exception(
                sprintf('Unknown response(%s) return.'), $response[2]
            );
        }

        $this->_rawData = $result;

        return $result[1];
    }

    /**
     * Get keys by tag.
     *
     * @param  mixed $tag
     * @param  mixed $returns
     * @access public
     * @return mixed Tags or null
     */
    public function getKeysByTag($tag, $returns = false)
    {
        if ($tag === null || $tag === false) {
            $this->_rawData = null;
            return null;
        }
        $command  = self::ID_TAG_SET . self::DATA_DELIMITER
                  . base64_encode($tag) . self::DATA_DELIMITER;
        $command  = ($returns === true) ? $command . 'true' : $command . 'false';
        $response = $this->_parse(
            $this->send($command . "\n")->response(), self::ID_TAG_GET
        );
        $result   = array($response[1], array());
        if ($response[1] === 'true') {
            $data = $response[2];
            if ($data === self::BLANK_STRING) {
                $this->_rawData = $result;

                return null;
            }

            $tags = explode(self::TAG_DELIMITER, trim($data));
            foreach ($tags as $v) {
                $result[1][] = base64_decode($v);
            }

            $this->_rawData = $result;

            return $result[1];
        } else if ($response['1'] === 'false') {
            $this->_rawData = $result;

            return null;
        }

        throw new Exception(sprintf('Unknown response(%s) return.', $response[2]));
    }

    /**
     * Set value.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @param  array $tags
     * @access public
     * @return \Net\Okuyama\Adapter\Socket Fluent interface.
     */
    public function set($key, $value, array $tags = array())
    {
        return $this->_set($key, $value, self::ID_SET, $tags);
    }

    /**
     * Add value.
     *
     * <pre>
     *   Can not override data.
     * </pre>
     *
     * @param  mixed $key
     * @param  mixed $value
     * @param  array $tags
     * @access public
     * @return \Net\Okuyama\Adapter\Socket Fluent interface.
     */
    public function add($key, $value, array $tags = array())
    {
        return $this->_set($key, $value, self::ID_ADD, $tags);
    }

    /**
     * Set|add value.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @param  array $tags
     * @access public
     * @throws \Net\Okuyama\Exception Overflow data size.
     * @return \Net\Okuyama\Adapter\Socket Fluent interface.
     */
    private function _set($key, $value, $type = self::ID_SET, array $tags = array())
    {
        $tpl = '%s(%s) size is overflow, allow max size is %s';
        if (strlen($key) > $this->_maxSize) {
            throw new Exception(sprintf($tpl, 'Key string', $key, $this->_maxSize));
        }

        // Is null allowed??
        if ($value === '' || $value === null) {
            $value = self::BLANK_STRING;
        } else {
            if (strlen($value) > $this->_maxSize) {
                throw new Exception(sprintf('Values', $value, $this->_maxSize));
            }
            $value = base64_encode($value);
        }

        $command = $type . self::DATA_DELIMITER
                 . base64_encode($key) . self::DATA_DELIMITER;

        if ($tags === array()) {
            $command = $command . self::BLANK_STRING;
        } else {
            $buffer = '';
            foreach ($tags as $tag) {
                if (strlen($tag) > $this->_maxSize) {
                    throw new Exception(sprintf($tpl, 'Tag string', $tag, $this->axSize));
                }
                $buffer .= self::TAG_DELIMITER . base64_encode($tag);
            }
            $command = $command . ltrim($buffer, self::TAG_DELIMITER);
        }

        $command  = $command . self::DATA_DELIMITER
                  . self::TRANSACTION_CODE . self::DATA_DELIMITER
                  . $value . "\n";

        $response = $this->_parse($this->send($command)->response(), $type);
        if ($response[1] === 'true') {
            $this->_rawData = array($response[1], $response[2]);
            return $this;
        } else if ($response[1] === 'false') {
            $this->_rawData = array($response[1], $response[2]);
            return false;
        }

        throw new Exception($response[2]);
    }

    /**
     * Remove data.
     *
     * @param  mixed $key
     * @access public
     * @return bool true: Sucess to remove data, false: Fail to remove data
     */
    public function remove($key)
    {
        if ($key === null || $key === '') {
            $this->_rawData = null;
            return false;
        }

        $command  = self::ID_REMOVE . self::DATA_DELIMITER
                  . base64_encode($key) . self::DATA_DELIMITER
                  . self::TRANSACTION_CODE . "\n";
        $response = $this->_parse($this->send($command)->response(), self::ID_REMOVE);
        $result   = array($response[1]);
        if ($response[1] === 'true') {
            if ($response[2] === self::BLANK_STRING) {
                $result[] = '';
            } else {
                $result[] = base64_decode($response[2]);
            }
            $this->_rawData = $result;

            return true;
        } else if ($response[1] === 'false') {
            $result[] = null;
        } else {
            $result[] = $response[2];
        }

        $this->_rawData = $result;

        return false;
    }

    /**
     * Lock.
     *
     * @access public
     * @return void
     */
    public function lock()
    {
        // TODO: Implements
    }

    /**
     * Unlock.
     *
     * @access public
     * @return void
     */
    public function unlock()
    {
        // TODO: Implements
    }

    /**
     * Play script
     *
     * @access public
     * @return void
     */
    public function playScript()
    {
        // TODO: Implements
    }

    /**
     * Send data to server.
     *
     * @param  mixed $value
     * @access public
     * @throws \Net\Okuyama\Exception Fail to write data
     * @return \Net\Okuyama\Adapter\Socket Fluent interface
     */
    public function send($value)
    {
        if (false === @fwrite($this->_socket, $value, strlen($value))) {
            throw new Exception('Error writing request.');
        }
        return $this;
    }

    /**
     * Get data from socket.
     *
     * @access public
     * @throws \Net\Okuyama\Exception Fail to get data
     * @return string Received socket data
     */
    public function response()
    {
        if (false === ($result = @fgets($this->_socket))) {
            throw new Exception('Error getting response.');
        }

        return $result;
    }

    /**
     * Parse response string.
     *
     * @param  mixed $response Response string
     * @param  mixed $id Proccess id
     * @access private
     * @throws \Net\Okuyama\Exception Proccess id is not excpted
     * @return array Split delimiter response string
     */
    private function _parse($response, $id)
    {
        $result = explode(self::DATA_DELIMITER, trim($response));
        if ($result[0] === $id) {
            return $result;
        }

        throw new Exception('Execute violation of validity.');
    }

    /**
     * Validate host format.
     *
     * @param  mixed $host
     * @access private
     * @return bool true: Valid host|IP, false: Not valid host|IP
     */
    private function _validateHostFormat($host)
    {
        $pattern = '/^[a-zA-Z0-9\.]+[:][0-9]+$/';
        if (preg_match($pattern, $host, $match)) {
            return true;
        }

        return false;
    }
}
