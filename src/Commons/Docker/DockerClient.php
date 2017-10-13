<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 10.10.17
 * Time: 13:20
 */

namespace Hanaboso\PipesFramework\Commons\Docker;

use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Client\Socket\Client as SocketHttpClient;
use Http\Message\MessageFactory;
use Http\Message\MessageFactory\GuzzleMessageFactory;

/**
 * Class DockerClient
 *
 * @package Hanaboso\PipesFramework\Commons\Docker
 */
class DockerClient
{

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var string
     */
    protected $version = '1.30';

    /**
     *
     * Client constructor.
     *
     * @param array  $connectOption
     * @param string $version
     */
    public function __construct(array $connectOption = [], string $version = '1.30')
    {
        if (empty($connectOption)) {
            $connectOption = $this->getDefault();
        }

        $this->messageFactory = new GuzzleMessageFactory();
        $socketClient         = new SocketHttpClient($this->messageFactory, $connectOption);

        $this->httpClient = $this->getHttpClient($socketClient);

        $this->version = $version;
    }

    /**
     * @param HttpClient $socketClient
     *
     * @return HttpClient
     */
    protected function getHttpClient(HttpClient $socketClient): HttpClient
    {
        return new PluginClient($socketClient, [
            new ErrorPlugin(),
            new ContentLengthPlugin(),
            new DecoderPlugin(),
        ]);
    }

    /**
     * @return array
     */
    protected function getDefault(): array
    {
        return [
            'remote_socket' => 'unix:///var/run/docker.sock',
        ];
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $headers
     * @param string $body
     *
     * @return DockerResult
     * @internal param RequestInterface $request
     */
    public function send(string $method, string $uri, array $headers = [],
                         string $body = ""): DockerResult
    {
        $request = $this->messageFactory->createRequest($method, $uri, $headers, $body);

        $response = $this->httpClient->sendRequest($request);

        return new DockerResult($response->getBody());
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

}
