<?php
/**
 * Spec of \Net\Okuyama.
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
use Net;

/**
 * @see prepare
 */
require_once dirname(__DIR__) . '/prepare.php';

/**
 * @see \Net\Okuyama
 */
require_once 'Net/Okuyama.php';

/**
 * Basic test.
 *
 * <pre>
 *   Before you run this test, make sure okuyama running.
 * </pre>
 *
 * @use       \Net
 * @category  \Net
 * @package   \Net\Okuyama
 * @version   $id$
 * @copyright (c) 2011 Shinya Ohyanagi
 * @author    Shinya Ohyanagi <sohyanagi@gmail.com>
 * @license   New BSD License
 */
class BasicTest extends \PHPUnit_Framework_TestCase
{
    const KEY_PREFIX = 'Net_Okuyama_Test_';

    private $_client = null;

    private $_hosts = array(
        '127.0.0.1:8888'
    );

    public function setUp()
    {
        $this->_client = new Okuyama();
    }

    public function testShouldCreateInstance()
    {
        $this->assertTrue($this->_client instanceof Okuyama);
    }

    public function testshouldConnetToHost()
    {
        $result = $this->_client->connect($this->_hosts);
        $this->assertTrue($result instanceof \Net\Okuyama);
    }

    public function testShouldCloseConnection()
    {
        $result = $this->_client->connect($this->_hosts);
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

        $this->_hosts[] = '127.0.0.1:8889';
        $result = $this->_client->connect($this->_hosts);
        $this->assertTrue($result instanceof \Net\Okuyama);
        $this->_client->close();
    }

    public function testShouldSetDataToServer()
    {
        $this->_client->connect($this->_hosts);
        $ret = $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $this->assertTrue($ret instanceof \Net\Okuyama);

        $value = $this->_client->get(self::KEY_PREFIX . 'foo');
        $this->assertSame($value, 'bar');
        $this->_client->close();
    }

    public function testShouldSetDataWithTag()
    {
        $this->_client->connect($this->_hosts);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $result = $this->_client->getKeysByTag('fiz');
        $this->assertSame($result, array(
            self::KEY_PREFIX . 'bar',
            self::KEY_PREFIX . 'hoge',
            self::KEY_PREFIX . 'foo'
        ));
        $this->_client->close();
    }

    public function testShouldGetDataWithTag()
    {
        $this->_client->connect($this->_hosts);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);
        $result = $this->_client->getKeysByTag('baz');
        $this->assertSame($result, array(
            self::KEY_PREFIX . 'bar',
            self::KEY_PREFIX . 'hoge',
            self::KEY_PREFIX . 'foo'
        ));
        $this->_client->close();
    }

    public function testShouldReturnNullWhenTagNotExitsts()
    {
        $this->_client->connect($this->_hosts);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $result = $this->_client->getKeysByTag('baz' . rand(0, 1000));
        $this->assertSame($result, null);
        $this->_client->close();
    }

    public function testShouldReturnNullWhenArgReturnsSetFalse()
    {
        $this->_client->connect($this->_hosts);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $this->_client->remove(self::KEY_PREFIX . 'bar');
        $this->_client->remove(self::KEY_PREFIX . 'hoge');
        $this->_client->remove(self::KEY_PREFIX . 'foo');
        $ret = $this->_client->get(self::KEY_PREFIX . 'bar');

        $result = $this->_client->getKeysByTag('baz', false);
        $this->assertSame($result, null);
        $this->_client->close();
    }

    public function testShouldReturnKeyWhenArgReturnsSetTrue()
    {
        $this->_client->connect($this->_hosts);
        $tags = array('fiz', 'baz');
        $this->_client->set(self::KEY_PREFIX . 'bar', 'foo', $tags)
                      ->set(self::KEY_PREFIX . 'hoge', 'fuga', $tags)
                      ->set(self::KEY_PREFIX . 'foo', 'bar', $tags);

        $this->_client->remove(self::KEY_PREFIX . 'bar');
        $this->_client->remove(self::KEY_PREFIX . 'hoge');
        $this->_client->remove(self::KEY_PREFIX . 'foo');

        $result = $this->_client->getKeysByTag('fiz', true);
        $this->assertSame($result, array(
            self::KEY_PREFIX . 'bar',
            self::KEY_PREFIX . 'hoge',
            self::KEY_PREFIX . 'foo'
        ));
        $this->_client->close();
    }

    public function testShouldReturnTrueWhenDeleteSuccess()
    {
        $this->_client->connect($this->_hosts);
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $ret = $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->assertTrue($ret);

        $value = $this->_client->get(self::KEY_PREFIX . 'foo');
        $this->assertSame($value, null);
        $this->_client->close();
    }

    public function testShouldReturnFalseWhenRemoveFalse()
    {
        $this->_client->connect($this->_hosts);
        $this->_client->set(self::KEY_PREFIX . 'foo', 'bar');
        $ret = $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->assertTrue($ret);

        $ret = $this->_client->remove(self::KEY_PREFIX . 'foo');
        $this->assertFalse($ret);
        $this->_client->close();
    }

    public function testShouldAddData()
    {
        $this->_client->connect($this->_hosts);
        $ret = $this->_client->remove(self::KEY_PREFIX . 'baz');
        $ret = $this->_client->add(self::KEY_PREFIX . 'baz', 'fiz');
        $this->assertTrue($ret instanceof \Net\Okuyama);

        $this->_client->close();
    }

    public function testShouldReturnFalseWhenDataAlreadyAdded()
    {
        $this->_client->connect($this->_hosts);
        $ret = $this->_client->remove(self::KEY_PREFIX . 'baz');
        $this->_client->add(self::KEY_PREFIX . 'baz', 'fiz');
        $ret = $this->_client->add(self::KEY_PREFIX . 'baz', 'fiz');

        $this->assertTrue($ret instanceof \Net\Okuyama);
        $this->_client->close();
    }

    public function testShouldSetDataWithUsingSetKeyPrefix()
    {
        $this->_client->connect($this->_hosts);
        $this->_client->setKeyPrefix(self::KEY_PREFIX);

        $tags = array('fiz', 'baz');
        $this->_client->set('bar', 'foo', $tags)
                      ->set('hoge', 'fuga', $tags)
                      ->set('foo', 'bar', $tags);

        $result = $this->_client->getKeysByTag('fiz', true);
        $this->assertSame($result, array(
            self::KEY_PREFIX . 'bar',
            self::KEY_PREFIX . 'hoge',
            self::KEY_PREFIX . 'foo'
        ));


        $this->_client->setKeyPrefix('');
        $this->assertSame($this->_client->get(self::KEY_PREFIX . 'bar'), 'foo');
        $this->assertSame($this->_client->get(self::KEY_PREFIX . 'hoge'), 'fuga');
        $this->assertSame($this->_client->get(self::KEY_PREFIX . 'foo'), 'bar');


        $this->_client->setKeyPrefix(self::KEY_PREFIX);
        $this->assertTrue($this->_client->remove('bar'));
        $this->assertTrue($this->_client->remove('hoge'));
        $this->assertTrue($this->_client->remove('foo'));

        $this->_client->close();
    }

    public function testShouldGetVersionNo()
    {
        $this->_client->connect($this->_hosts);
        $this->_client->setKeyPrefix(self::KEY_PREFIX);
        $this->_client->remove('foo');
        $this->_client->set('foo', 'bar');

        $ret = $this->_client->gets('foo');
        $this->assertSame($ret, array('value' => 'bar', 'version' => 0));

        $this->_client->close();
    }

    public function testShouldCheackAndVersion()
    {
        $this->_client->connect($this->_hosts);
        $this->_client->setKeyPrefix(self::KEY_PREFIX);
        $this->_client->remove('foo');
        $this->_client->set('foo', 'bar');

        $ret = $this->_client->gets('foo');
        $this->_client->cas('foo', 'foo', $ret['version']);
        $ret = $this->_client->gets('foo');
        $this->assertSame($ret, array('value' => 'foo', 'version' => 1));
        $this->_client->close();
    }

    public function testShouldSetFailedWhenSetOldVersionNo()
    {
        $this->_client->connect($this->_hosts);
        $this->_client->setKeyPrefix(self::KEY_PREFIX);
        $this->_client->remove('foo');
        $this->_client->set('foo', 'bar');

        $ret = $this->_client->gets('foo');
        $this->_client->cas('foo', 'foo', $ret['version']);
        $ret = $this->_client->gets('foo');
        $this->assertSame($ret, array('value' => 'foo', 'version' => 1));

        $ret = $this->_client->cas('foo', 'baz', 0);
        $this->assertFalse($ret);
        $ret = $this->_client->gets('foo');
        $this->assertSame($ret, array('value' => 'foo', 'version' => 1));
        $this->_client->close();
    }

    public function testShouldRunJavaScriptCode()
    {
        $this->_client->connect($this->_hosts);
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
        $this->_client->connect($this->_hosts);
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
}
