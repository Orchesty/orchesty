<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 13.10.17
 * Time: 11:33
 */

namespace Tests\Unit\Commons\Docker\Endpoint;

use Hanaboso\PipesFramework\Commons\Docker\DockerClient;
use Hanaboso\PipesFramework\Commons\Docker\DockerResult;
use Hanaboso\PipesFramework\Commons\Docker\Endpoint\Containers;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * Class ContainersTest
 *
 * @package Tests\Unit\Commons\Docker\Endpoint
 */
class ContainersTest extends TestCase
{

    /**
     * @dataProvider List
     * @covers       Containers::list()
     *
     * @param array  $params
     * @param array  $filters
     * @param string $endpointUrl
     * @param array  $headers
     * @param string $body
     */
    public function testList(array $params, array $filters, string $endpointUrl, array $headers, string $body): void
    {
        $dockerClient = $this
            ->getMockBuilder(DockerClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dockerClient->method('getVersion')->willReturn('1.30');

        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->getMock();
        $stream->method('getContents')->willReturn('[{}]');

        /** @var StreamInterface $stream */
        $dockerResult = new DockerResult($stream);

        $dockerClient->expects($this->once())->method('send')->with('GET', $endpointUrl, $headers, $body)
            ->willReturn($dockerResult);

        /** @var DockerClient $dockerClient */
        $container = new Containers($dockerClient);
        $container->list($params, $filters);
    }

    /**
     * @return array
     */
    public function list(): array
    {
        return [
            [[], [], 'http://v1.30/containers/json', [], ''],
            [['all' => TRUE], [], 'http://v1.30/containers/json?all=1', [], ''],
            [
                ['all' => TRUE, 'limit' => 10, 'size' => TRUE],
                [],
                'http://v1.30/containers/json?all=1&limit=10&size=1',
                [],
                '',
            ],
            [
                [],
                ['label' => ['label=A'], 'status' => ['created']],
                'http://v1.30/containers/json?filters=%7B%22label%22%3A%5B%22label%3DA%22%5D%2C%22status%22%3A%5B%22created%22%5D%7D',
                [],
                '',
            ],
            [
                ['limit' => 10],
                ['label' => ['label=A'], 'status' => ['created']],
                'http://v1.30/containers/json?limit=10&filters=%7B%22label%22%3A%5B%22label%3DA%22%5D%2C%22status%22%3A%5B%22created%22%5D%7D',
                [],
                '',
            ],
        ];
    }

}
