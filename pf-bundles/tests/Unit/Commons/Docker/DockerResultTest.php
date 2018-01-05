<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 13.10.17
 * Time: 9:53
 */

namespace Tests\Unit\Commons\Docker;

use Hanaboso\PipesFramework\Commons\Docker\DockerResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * Class DockerResultTest
 *
 * @package Tests\Unit\Commons\Docker
 */
class DockerResultTest extends TestCase
{

    /**
     * @var StreamInterface|MockObject
     */
    protected $stream;

    /**
     * setUp
     */
    public function setUp(): void
    {
        $this->stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->getMock();
        $this->stream->method('getContents')->willReturn('[{}]');
        $this->stream->method('getSize')->willReturn(4);

    }

    /**
     * @covers DockerResult::getResult()
     */
    public function testGetResult(): void
    {
        $resutl = new DockerResult($this->stream);
        $this->assertEquals($this->stream, $resutl->getResult());
    }

    /**
     * @covers DockerResult::getContent()
     */
    public function testGetContent(): void
    {
        $resutl = new DockerResult($this->stream);
        $this->assertEquals('[{}]', $resutl->getContent());
    }

    /**
     * @covers DockerResult::getSize()
     */
    public function testGetSize(): void
    {
        $resutl = new DockerResult($this->stream);
        $this->assertEquals(4, $resutl->getSize());
    }

}
