<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Session\Handler;

use Hanaboso\PipesFramework\Commons\Session\Handler\RedisSessionHandler;
use Tests\KernelTestCaseAbstract;

class RedisSessionHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var RedisSessionHandler
     */
    private $handler;

    public function setUp()
    {
        $this->handler = $this->container->get('hbpf.commons.session_handler');
    }

    /**
     * @covers RedisSessionHandler::open()
     * @throws \Exception
     */
    public function testOpen()
    {
        $this->assertTrue($this->handler->open('some/path', 'some name'));
        $this->assertTrue($this->handler->open('some/path', 'another name'));
    }

    /**
     * @covers RedisSessionHandler::close()
     * @throws \Exception
     */
    public function testClose()
    {
        $this->assertTrue($this->handler->close());
    }

    /**
     * @covers RedisSessionHandler::gc()
     * @throws \Exception
     */
    public function testGc()
    {
        $this->assertTrue($this->handler->gc(0));
        $this->assertTrue($this->handler->gc(999));
        $this->assertTrue($this->handler->gc("aaa"));
    }

    /**
     * @covers RedisSessionHandler::read()
     * @covers RedisSessionHandler::write()
     * @covers RedisSessionHandler::destroy()
     * @throws \Exception
     */
    public function testReadWriteDestroy()
    {
        $this->assertTrue($this->handler->destroy("foo"));
        $this->assertEmpty($this->handler->read("foo"));
        $this->assertTrue($this->handler->write("foo", "data"));
        $this->assertEquals("data", $this->handler->read("foo"));
        $this->assertTrue($this->handler->write("foo", "new data"));
        $this->assertEquals("new data", $this->handler->read("foo"));
        $this->assertTrue($this->handler->destroy("foo"));
        $this->assertEmpty($this->handler->read("foo"));
    }

}
