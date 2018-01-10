<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 13.10.17
 * Time: 10:53
 */

namespace Tests\Unit\Commons\Docker;

use Hanaboso\PipesFramework\Commons\Docker\Docker;
use Hanaboso\PipesFramework\Commons\Docker\DockerClient;
use Hanaboso\PipesFramework\Commons\Docker\Endpoint\Containers;
use Hanaboso\PipesFramework\Commons\Docker\Endpoint\EndpointAbstract;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Exception\NotImplementedException;

/**
 * Class DockerTest
 *
 * @package Tests\Unit\Commons\Docker
 */
class DockerTest extends TestCase
{

    /**
     * @var DockerClient|MockObject
     */
    protected $dockerClient;

    /**
     * setUp
     */
    public function setUp(): void
    {
        $this->dockerClient = $this
            ->getMockBuilder(DockerClient::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider getEndpoint
     * @covers       Docker::getEndpoint()
     *
     * @param string      $endpoint
     * @param null|string $class
     * @param null|string $exception
     */
    public function testGetEndpoint(string $endpoint, ?string $class, ?string $exception): void
    {
        $docker = new Docker($this->dockerClient);
        if ($exception) {
            $this->expectException($exception);
        }

        $result = $docker->getEndpoint($endpoint);
        if ($class) {
            $this->assertInstanceOf($class, $result);
            $this->assertInstanceOf(EndpointAbstract::class, $result);
        }
    }

    /**
     * @return array
     */
    public function getEndpoint(): array
    {
        return [
            ['containers', Containers::class, NULL],
            [Docker::COINTAINERS, Containers::class, NULL],
            ['image', NULL, NotImplementedException::class],
        ];
    }

}
