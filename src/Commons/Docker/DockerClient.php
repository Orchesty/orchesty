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
use Psr\Http\Message\RequestInterface;

class DockerClient
{

    public const RESULT_AS_ARRAY = 1;

    public const RESULT_AS_OBJECT = 2;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $serializer;

    /**
     * @var string
     */
    protected $version;

    //    public function _send()
    //    {
    //        //curl -G XGET --unix-socket /var/run/docker-pavel.severyn.sock http:/v1.30/containers/json --data-urlencode 'filters={"label":["com.docker.compose.project=59d5f1cf2b493c00157e3ca9aaa"], "status":["running"]}'
    //
    //        $this->messageFactory = new GuzzleMessageFactory();
    //        $socketClient         = new SocketHttpClient($this->messageFactory,
    //            ['remote_socket' => 'unix:///var/run/docker.sock']);
    //        $lengthPlugin         = new ContentLengthPlugin();
    //        $decodingPlugin       = new DecoderPlugin();
    //        $errorPlugin          = new ErrorPlugin();
    //
    //        $httpClient = new PluginClient($socketClient, [
    //            $errorPlugin,
    //            $lengthPlugin,
    //            $decodingPlugin,
    //        ]);
    //
    //        $request  = $messageFactory->createRequest('GET',
    //            'http://v1.30/containers/json?filters=%7B%22label%22%3A%5B%22com.docker.compose.project%3D59d5f1cf2b493c00157e3ca9aaa%22%5D%2C%20%22status%22%3A%5B%22running%22%5D%7D',
    //            [], "");
    //        $response = $httpClient->sendRequest($request);
    //        $json     = json_decode($response->getBody()->getContents(), TRUE);
    //        print_r(count($json));
    //        print_r($json);
    //
    //    }

    /**
     *
     * Client constructor.
     *
     * @param array  $connectOption
     * @param array  $serializer
     * @param string $version
     */
    public function __construct(array $connectOption = ['remote_socket' => 'unix:///var/run/docker.sock'],
                                $serializer = [], string $version = '1.30')
    {
        $this->messageFactory = new GuzzleMessageFactory();
        $socketClient         = new SocketHttpClient($this->messageFactory, $connectOption);

        $this->httpClient = new PluginClient($socketClient, [
            new ErrorPlugin(),
            new ContentLengthPlugin(),
            new DecoderPlugin(),
        ]);

        $this->serializer = $serializer;
        $this->version    = $version;
    }

    /**
     * @param RequestInterface $request
     */
    public function send(RequestInterface $request, $resultAs = self::RESULT_AS_ARRAY)
    {
        $response = $this->httpClient->sendRequest($request);

        if ($resultAs == self::RESULT_AS_ARRAY) {
            return json_decode($response->getBody()->getContents(), TRUE);
        } elseif ($resultAs == self::RESULT_AS_OBJECT) {
            return json_decode($response->getBody()->getContents());
        }

        return $response->getBody()->getContents();
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $headers
     * @param string $body
     *
     * @return RequestInterface
     */
    public function prepareRequest(string $method, string $uri, array $headers = [],
                                   string $body = ""): RequestInterface
    {
        return $this->messageFactory->createRequest($method, $uri, $headers, $body);
    }

}
