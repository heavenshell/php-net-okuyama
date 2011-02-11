<?php
/**
 * Okuyama(Distributed key-value-store) client library.
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
 * @use       \Net
 * @category  \Net
 * @package   \Net\Okuyama
 * @version   $id$
 * @copyright (c) 2011 Shinya Ohyanagi
 * @author    Shinya Ohyanagi <sohyanagi@gmail.com>
 * @license   New BSD License
 */

namespace Net;
use Net,
    Net\Okuyama\Exception;

/**
 * Okuyama(Distributed key-value-store) client library.
 *
 * @use       \Net
 * @category  \Net
 * @package   \Net\KyotoTycoon
 * @version   $id$
 * @copyright (c) 2011 Shinya Ohyanagi
 * @author    Shinya Ohyanagi <sohyanagi@gmail.com>
 * @license   New BSD License
 */
class Okuyama
{
    /**
     * Version.
     */
    const VERSION = '0.0.2';

    /**
     * Client to access Okuyama.
     *
     * @var    mixed
     * @access protected
     */
    protected $_client = null;

    /**
     * Adapter class.
     *
     * @var    mixed
     * @access protected
     */
    protected $_adapter = '\Net\Okuyama\Adapter\Socket';

    /**
     * Key prefix.
     *
     * @var    string
     * @access protected
     */
    protected $_keyPrefix = '';

    /**
     * Get key prefix.
     *
     * @access public
     * @return string Key prefix
     */
    public function getKeyPrefix()
    {
        return $this->_keyPrefix;
    }

    /**
     * Set key prefix.
     *
     * @param  mixed $prefix
     * @access public
     * @return \Net\Okuyama Fluent interface
     */
    public function setKeyPrefix($prefix)
    {
        $this->_keyPrefix = $prefix;
        return $this;
    }

    /**
     * Create client instance.
     *
     * @param  mixed $adapter
     * @access public
     * @return Net\Okuyama Fluent interface
     */
    public function createInstance($adapter)
    {
        // TODO: Use ReflectionClass.
        $this->_client = new $adapter;
        return $this;
    }

    /**
     * Set config options.
     *
     * @param  array $configs
     * @access public
     * @return \Net\Okuyama Fluent interface
     */
    public function setConfig(array $configs)
    {
        if ($this->_adapter === null) {
            throw new Exception('Adapter does not set.');
        }
        $this->_client->setConfig($configs);

        return $this;
    }

    /**
     * Constructor
     *
     * <pre>
     *   array(
     *     'adapter' => '\Net\Okuyama\Adapter\Socket', // Adapter class name.
     *     'timeout' => 10 // Timeout second.
     *   );
     * </pre>
     *
     * @param  array $configs
     * @access public
     * @return void
     */
    public function __construct(array $configs = array())
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
        if (isset($configs['adapter'])) {
            $this->_adapter = $configs['adapter'];
        }

        $this->createInstance($this->_adapter);
        if ($configs !== array()) {
            $this->setConfig($configs);
        }
    }

    /**
     * Connect to server.
     *
     * @param  array $hosts
     * @access public
     * @return \Net\Okuyama Fluent interface
     */
    public function connect(array $hosts)
    {
        $ret = $this->_client->autoConnect($hosts);
        if ($ret === false) {
            throw new Exception('Connection refused.');
        }

        return $this;
    }

    /**
     * Close connection.
     *
     * @access public
     * @return bool true: Success to close
     */
    public function close()
    {
        return $this->_client->close();
    }

    /**
     * Is connected?
     *
     * @access public
     * @return bool true: Connected, false: Not connected
     */
    public function isConnected()
    {
        return $this->_client->isConnected();
    }

    /**
     * Retrieve the value of a record.
     *
     * @param  mixed $key Key string
     * @param  mixed $tag Tag string
     * @access public
     * @return mixed Value.
     */
    public function get($key)
    {
        return $this->_client->get($this->_keyPrefix . $key);
    }

    /**
     * Set the value of a record.
     *
     * @param  mixed $key The key of the record
     * @param  mixed $value The value of the record
     * @param  mixed $tag Tags
     * @access public
     * @return \Net\Okuyama Fluent interface
     */
    public function set($key, $value, array $tags = array())
    {
        $ret = $this->_client->set($this->_keyPrefix . $key, $value, $tags);
        return $this;
    }

    /**
     * Add a record.
     *
     * @param  mixed $key The key of the record
     * @param  mixed $value The value of the record
     * @param  mixed $tag Tags
     * @access public
     * @return \Net\Okuyama Fluent interface
     */
    public function add($key, $value, array $tags = array())
    {
        $ret = $this->_client->add($this->_keyPrefix . $key, $value, $tags);

        return $this;
    }

    /**
     * Get version no and value at once.
     *
     * <pre>
     *   Equivalent to Memcached gets.
     * </pre>
     *
     * @param  mixed $key The key of the record
     * @access public
     * @return array The value of the record and version no
     */
    public function gets($key)
    {
        return $this->_client->gets($this->_keyPrefix . $key);
    }

    /**
     * Check and set record.
     *
     * <pre>
     *   Equivalent to Memcached cas.
     * </pre>
     *
     * @param  mixed $key The key of the record
     * @param  mixed $value The value of the record
     * @param  array $tags Tags
     * @param  mixed $version Version no
     * @access public
     * @return mixed Fluent interface or false
     */
    public function cas($key, $value, $version, array $tags = array())
    {
        return $this->_client->cas($this->_keyPrefix . $key, $value, $version, $tags);
    }

    /**
     * Remove a record.
     *
     * @param  mixed $key The key of the record
     * @access public
     * @return mixed Result of remove command
     */
    public function remove($key)
    {
        return $this->_client->remove($this->_keyPrefix . $key);
    }

    /**
     * Get keys by tag.
     *
     * @param  mixed $tag Tags
     * @param  mixed $returns
     * @access public
     * @return mixed Keys or null
     */
    public function getKeysByTag($tag, $returns = false)
    {
        return $this->_client->getKeysByTag($tag, $returns);
    }

    /**
     * Retrieve records at once
     *
     * @param  array $key The key of the record
     * @access public
     * @return array Values
     */
    public function getBulk(array $keys)
    {
        $result = array();
        foreach ($keys as $key) {
            $result[$key] = $this->_client->get($this->_keyPrefix . $key);
        }

        return $result;
    }

    /**
     * Set record at once.
     *
     * @param  array $vals
     * @access public
     * @return \Net\Okuyama Fluent interface
     */
    public function setBulk(array $vals)
    {
        foreach ($vals as $v) {
            if (isset($v['key']) && isset($v['value'])) {
                $tags = (isset($v['tags']) && is_array($v['tags'])) ? $v['tags'] : null;

                $this->_client->set(
                    $this->_keyPrefix . $v['key'], $v['value'], $tags
                );
            }
        }

        return $this;
    }

    /**
     * Remove at onece.
     *
     * @param  array $key The key of the record
     * @access public
     * @return array Result of remove command
     */
    public function removeBulk(array $keys)
    {
        $result = array();
        foreach ($keys as $key) {
            $result[$key] = $this->_client->remove($this->_keyPrefix . $key);
        }

        return $result;
    }

    public function playScript($key, $script, $update = false)
    {
        return $this->_client->playScript($this->_keyPrefix . $key, $script, $update);
    }

    /**
     * Remove tag.
     *
     * @param mixed $tag
     * @access public
     * @return void
     */
    public function removeTag($tag)
    {
        throw new Exception('Not Implemented.');
    }

    /**
     * Clear all data.
     *
     * @access public
     * @return void
     */
    public function clear()
    {
        throw new Exception('Not Implemented.');
    }

    /**
     * Autoload class.
     *
     * @param  mixed $class
     * @access public
     * @return void
     */
    public static function autoload($className)
    {
        // Autoload class.
        // http://groups.google.com/group/php-standards/web/psr-0-final-proposal
        if (!class_exists($className, false)) {
            $className = ltrim($className, '\\');
            $fileName  = '';
            $namespace = '';
            if ($lastNsPos = strripos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

            require_once $fileName;
        }
    }
}
