<?php
/**
 * Spec of \Net\Okuyama\Adapter\Socket.
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

namespace Net\Okuyama;
use Net\Okuyama;

/**
 * @see prepare
 */
require_once dirname(__DIR__) . '/prepare.php';

/**
 * @see \Net\Okuyama\Exception
 */
require_once 'Net/Okuyama/Exception.php';

/**
 * @see \Net\Okuyama\Adapter
 */
require_once 'Net/Okuyama/Adapter.php';

/**
 * @see \Net\Okuyama\Adapter\Socket
 */
require_once 'Net/Okuyama/Adapter/Socket.php';

/**
 * Socket test.
 *
 * <pre>
 *   Before you run this test, make sure okuyama running.
 * </pre>
 *
 * @use       \Net\Okuyama
 * @category  \Net
 * @package   \Net\Okuyama
 * @version   $id$
 * @copyright (c) 2011 Shinya Ohyanagi
 * @author    Shinya Ohyanagi <sohyanagi@gmail.com>
 * @license   New BSD License
 */
class SocketTest extends \PHPUnit_Framework_TestCase
{
    const KEY_PREFIX = 'Net_Okuyama_Test_';
    const HOST = '127.0.0.1';
    const PORT = 8888;

    private $_client = null;

    public function setUp()
    {
        $this->_client = new Okuyama\Adapter\Socket();
    }

    public function testShouldCreateInstance()
    {
        $this->assertTrue($this->_client instanceof Okuyama\Adapter\Socket);
    }

    public function testShouldConnectToHost()
    {
        $result = $this->_client->connect(self::HOST, self::PORT);
        $this->assertInternalType('resource', $result);
        $this->assertTrue($this->_client->close());
    }

    public function testShouldCloseConnection()
    {
        $result = $this->_client->connect(self::HOST, self::PORT);
        $this->assertTrue($this->_client->close());
    }

    /**
     * @expectedException \Net\Okuyama\Exception
     */
    public function testShouldThrowsExceptionWhenConnctionCloseFailed()
    {
        $this->_client->close();
    }

    public function testShouldConnectToHostAuto()
    {
        $hosts = array(
            self::HOST . ':' . (self::PORT + 1),
            self::HOST . ':' . self::PORT
        );
        $result = $this->_client->autoConnect($hosts);
        $this->assertTrue($result);
        $this->_client->close();
    }

    public function testShouldSetDataToServer()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $ret = $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $this->assertTrue($ret instanceof \Net\Okuyama\Adapter\Socket);

        $value = $this->_client->get(self::KEY_PREFIX . 'foo');
        $this->assertSame($value, 'bar');
        $this->_client->close();
    }

    public function testShouldSetDataWithTag()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $result = $this->_client->getKeysByTag('fiz');
        $keys   = array(
            self::KEY_PREFIX . 'foo',
            self::KEY_PREFIX . 'bar',
            self::KEY_PREFIX . 'hoge',
        );
        foreach ($keys as $key) {
            $this->assertTrue(in_array($key, $result));
        }
        $this->_client->close();
    }

    public function testShouldGetDataWithTag()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);
        $result = $this->_client->getKeysByTag('baz');
        $keys   = array(
            self::KEY_PREFIX . 'foo',
            self::KEY_PREFIX . 'bar',
            self::KEY_PREFIX . 'hoge',
        );
        foreach ($keys as $key) {
            $this->assertTrue(in_array($key, $result));
        }

        $this->_client->close();
    }

    public function testShouldReturnNullWhenTagNotExitsts()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $result = $this->_client->getKeysByTag(self::KEY_PREFIX . 'baz' . rand(0, 1000));
        $this->assertSame($result, null);
        $this->_client->close();
    }

    public function testShouldReturnNullWhenArgReturnsSetFalse()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $this->_client->remove(self::KEY_PREFIX . 'bar');
        $this->_client->remove(self::KEY_PREFIX . 'hoge');
        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $ret = $this->_client->get('bar');

        $result = $this->_client->getKeysByTag('baz', false);
        $this->assertSame($result, null);
        $this->_client->close();
    }

    public function testShouldReturnKeyWhenArgReturnsSetTrue()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $this->_client->remove(self::KEY_PREFIX . 'bar');
        $this->_client->remove(self::KEY_PREFIX . 'hoge');
        $this->_client->remove(self::KEY_PREFIX . 'foo');

        $result = $this->_client->getKeysByTag('fiz', true);
        $keys   = array(
            self::KEY_PREFIX . 'foo',
            self::KEY_PREFIX . 'bar',
            self::KEY_PREFIX . 'hoge',
        );
        foreach ($keys as $key) {
            $this->assertTrue(in_array($key, $result));
        }
        $this->_client->close();
    }

    public function testShouldReturnTrueWhenDeleteSuccess()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $ret = $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->assertTrue($ret);

        $value = $this->_client->get(self::KEY_PREFIX . 'foo');
        $this->assertSame($value, null);
        $this->_client->close();
    }

    public function testShouldReturnFalseWhenRemoveFalse()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $ret = $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->assertTrue($ret);

        $ret = $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->assertFalse($ret);
        $this->_client->close();
    }

    public function testShouldAddData()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $ret = $this->_client->remove(self::KEY_PREFIX . 'baz');
        $ret = $this->_client->add(self::KEY_PREFIX . 'baz', 'fiz');
        $this->assertTrue($ret instanceof \Net\Okuyama\Adapter\Socket);
        $this->_client->close();
    }

    public function testShouldReturnFalseWhenDataAlreadyAdded()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $ret = $this->_client->remove(self::KEY_PREFIX . 'baz');
        $this->_client->add(self::KEY_PREFIX . 'baz', 'fiz');
        $ret = $this->_client->add(self::KEY_PREFIX . 'baz', 'fiz');
        $this->assertFalse($ret);

        $this->_client->close();
    }

    public function testShouldGetVersionNo()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');

        $ret = $this->_client->gets(self::KEY_PREFIX . 'foo');
        $this->assertRegExp('/^[0-9]+/', strval($ret['version']));

        $this->_client->close();
    }

    public function testShouldCheackAndVersion()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');

        $ret = $this->_client->gets(self::KEY_PREFIX . 'foo');
        $ver = $ret['version'];
        $this->_client->cas(self::KEY_PREFIX . 'foo', 'foo', $ret['version']);
        $ret = $this->_client->gets(self::KEY_PREFIX . 'foo');
        $this->assertSame($ret['value'], 'foo');
        $this->assertNotSame($ret['version'], $ver);
        $this->_client->close();
    }

    public function testShouldSetFailedWhenSetOldVersionNo()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');

        $ret = $this->_client->gets(self::KEY_PREFIX . 'foo');
        $this->_client->cas(self::KEY_PREFIX . 'foo', 'foo', $ret['version']);
        $ver = $ret['version'];
        $ret = $this->_client->gets(self::KEY_PREFIX . 'foo');
        $this->assertNotSame($ret['version'], $ver);
        $ver2 = $ret['version'];

        $ret = $this->_client->cas(self::KEY_PREFIX . 'foo', 'baz', 0);
        $this->assertFalse($ret);
        $ret = $this->_client->gets(self::KEY_PREFIX . 'foo');
        $this->assertSame($ret['version'], $ver2);
        $this->_client->close();
    }

    public function testShouldRunJavaScriptCode()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $script = <<<EOT
var dataValue;
var retValue = 'foo' + dataValue;
var execRet = '1';
EOT;
        $ret = $this->_client->playScript(self::KEY_PREFIX . 'foo', $script);
        $this->assertSame($ret, 'foobar');
        $ret = $this->_client->get(self::KEY_PREFIX . 'foo');
        $this->assertSame($ret, 'bar');

        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->_client->close();
    }

    public function testShouldRunJavaScriptCodeAndUpdateValue()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $script = <<<EOT
var dataValue;
var retValue = 'foo' + dataValue;
var execRet = '2';
EOT;
        $ret = $this->_client->playScript(self::KEY_PREFIX . 'foo', $script, true);
        $this->assertSame($ret, 'foobar');
        $ret = $this->_client->get(self::KEY_PREFIX . 'foo');
        $this->assertSame($ret, 'foobar');

        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->_client->close();
    }

    public function testShouldGetRawData()
    {
        $this->_client->connect(self::HOST, self::PORT);
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertRegExp('/\d+/', $result[1]);

        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'OK');

        $this->_client->get(self::KEY_PREFIX . 'foo');
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'bar');

        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar', $tags);
        $this->_client->getKeysByTag('fiz');
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], array(self::KEY_PREFIX . 'foo'));

        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $this->_client->gets(self::KEY_PREFIX . 'foo');
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'bar');
        $this->assertRegExp('/^[0-9]+/', $result[2]);
        $ver = intval($result[2]);

        $this->_client->cas(self::KEY_PREFIX . 'foo', 'foobar', $ver);
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'OK');

        $this->_client->gets(self::KEY_PREFIX . 'foo');
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'foobar');
        $this->assertNotSame(intval($result[2]), $ver);

        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'foobar');


        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $script = <<<EOT
var dataValue;
var retValue = 'foo' + dataValue;
var execRet = '1';
EOT;
        $this->_client->playScript(self::KEY_PREFIX . 'foo', $script);
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'foobar');

        $script = <<<EOT
var retValue = 'foo';
var execRet = '2';
EOT;
        $this->_client->playScript(self::KEY_PREFIX . 'foo', $script, true);
        $result = $this->_client->getRawData();
        $this->assertSame($result[0], 'true');
        $this->assertSame($result[1], 'foo');

        $this->_client->close();
    }
}
