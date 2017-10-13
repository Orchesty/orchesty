<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 10.10.17
 * Time: 13:34
 */

namespace Tests\Unit\Commons\Docker;

use Hanaboso\PipesFramework\Commons\Docker\DockerClient;
use Hanaboso\PipesFramework\Commons\Docker\DockerResult;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\PrivateTrait;

/**
 * Class DockerClientTest
 *
 * @package Tests\Unit\Commons\Docker
 */
class DockerClientTest extends TestCase
{

    use PrivateTrait;

    /**
     * @covers DockerClient::__construct
     */
    public function testCreateClient(): void
    {
        $client = new DockerClient();

        $this->assertInstanceOf(PluginClient::class, $this->getProperty($client, 'httpClient'));
        $this->assertEquals('1.30', $this->getProperty($client, 'version'));
    }

    /**
     * @covers DockerClient::getDefault()
     */
    public function testGetDeafult(): void
    {
        $client = new DockerClient();
        $this->assertEquals(
            ['remote_socket' => 'unix:///var/run/docker.sock'],
            $this->invokeMethod($client, 'getDefault')
        );
    }

    /**
     * @covers DockerClient::send()
     */
    public function testSend(): void
    {
        $client = new DockerClient();

        $stream = $this
            ->getMockBuilder(StreamInterface::class)
            ->getMock();
        $stream->method('getContents')->willReturn('[{}]');

        $responseInterface = $this
            ->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $responseInterface->expects($this->once())->method('getBody')->willReturn($stream);

        $httpClient = $this
            ->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpClient->expects($this->once())->method('sendRequest')->willReturn($responseInterface);

        $this->setProperty($client, 'httpClient', $httpClient);

        $result = $client->send('GET', 'https://www.example.com', [], '');
        $this->assertInstanceOf(DockerResult::class, $result);

        $this->assertEquals('[{}]', $result->getContent());
    }

}
