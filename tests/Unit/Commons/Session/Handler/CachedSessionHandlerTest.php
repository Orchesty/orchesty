<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Session\Handler;

use Hanaboso\PipesFramework\Commons\Session\Handler\CachedSessionHandler;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;

/**
 * Class CachedSessionHandlerTest
 *
 * @package Tests\Unit\Commons\Session\Handler
 */
final class CachedSessionHandlerTest extends TestCase
{

    /**
     * @var CachedSessionHandler
     */
    private $csh;

    /**
     * @throws \ReflectionException
     */
    public function setUp(): void
    {
        $sh = $this->createMock(SessionHandlerInterface::class);
        $sh->method('destroy')->willReturn(TRUE);
        $sh->method('write')->willReturn(TRUE);
        $sh->method('read')->willReturn('default');

        /** @var SessionHandlerInterface $sh */
        $this->csh = new CachedSessionHandler($sh);
    }

    /**
     * @throws \Exception
     */
    public function testApcu(): void
    {
        $this->assertEquals([], apcu_exists(['foo', 'bar']));

        $this->assertTrue(apcu_add('foo', 'val'));
        $this->assertEquals(['foo' => TRUE], apcu_exists(['foo', 'bar']));
        $this->assertEquals('val', apcu_fetch('foo'));
        $this->assertFalse(apcu_fetch('bar'));

        $this->assertTrue(apcu_add('bar', 'val'));
        $this->assertEquals(['foo' => TRUE, 'bar' => TRUE], apcu_exists(['foo', 'bar']));
        $this->assertEquals('val', apcu_fetch('foo'));
        $this->assertEquals('val', apcu_fetch('bar'));

        $this->assertTrue(apcu_delete('foo'));
        $this->assertTrue(apcu_delete('bar'));
        $this->assertEquals([], apcu_exists(['foo', 'bar']));
    }

    /**
     * @covers CachedSessionHandler::read()
     * @covers CachedSessionHandler::write()
     * @covers CachedSessionHandler::destroy()
     * @throws \Exception
     */
    public function testReadWriteDestroy(): void
    {
        $this->assertTrue($this->csh->destroy('foo'));
        $this->assertEquals('default', $this->csh->read('foo'));

        $this->assertTrue($this->csh->write('foo', 'bar'));
        $this->assertEquals('bar', $this->csh->read('foo'));

        $this->assertTrue($this->csh->destroy('foo'));
        $this->assertEquals('default', $this->csh->read('foo'));
    }

    /**
     * @covers CachedSessionHandler::read()
     * @throws \Exception
     */
    public function testCacheTimeout(): void
    {
        $this->csh->setTimeout(1);

        $this->assertTrue($this->csh->destroy('foo'));
        $this->assertEquals('default', $this->csh->read('foo'));

        $this->assertTrue($this->csh->write('foo', 'val'));
        $this->assertEquals('val', $this->csh->read('foo'));

        sleep(1);
        $this->assertEquals('default', $this->csh->read('foo'));
    }

}
