<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 13.10.17
 * Time: 11:05
 */

namespace Tests\Unit\Commons\Docker\Handler;

use Hanaboso\PipesFramework\Commons\Docker\Docker;
use Hanaboso\PipesFramework\Commons\Docker\Endpoint\Containers;
use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class DockerHandlerTest
 *
 * @package Tests\Unit\Commons\Docker\Handler
 */
class DockerHandlerTest extends TestCase
{

    /**
     * @dataProvider getTopologyInfo
     * @covers       DockerHandler::getTopologyInfo()
     *
     * @param string      $projectName
     * @param array       $filter
     * @param null|string $status
     * @param array       $return
     */
    public function testGetTopologyInfo(string $projectName, array $filter, ?string $status, array $return): void
    {
        $containers = $this
            ->getMockBuilder(Containers::class)
            ->setMethods(['list'])
            ->disableOriginalConstructor()
            ->getMock();
        $containers->expects($this->once())->method('list')->with([], $filter)->willReturn($return);

        $docker = $this
            ->getMockBuilder(Docker::class)
            ->setMethods(['getEndpoint'])
            ->disableOriginalConstructor()
            ->getMock();
        $docker->expects($this->once())->method('getEndpoint')->willReturn($containers);

        /** @var Docker $docker */
        $dockerHandler = new DockerHandler($docker);

        $result = $dockerHandler->getTopologyInfo($projectName, $status);
        $this->assertEquals($return, $result);
    }

    /**
     * @return array
     */
    public function getTopologyInfo(): array
    {
        return [
            [
                'foo',
                ['label' => ['com.docker.compose.project=foo']],
                NULL,
                ['a' => 1, 'b' => 2],
            ],
            [
                'boo',
                ['label' => ['com.docker.compose.project=boo'], 'status' => ['running']],
                'running',
                ['a' => 1, 'b' => 2],
            ],
        ];
    }

}
