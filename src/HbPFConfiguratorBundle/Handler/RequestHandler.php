<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 17.10.17
 * Time: 8:28
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;

/**
 * Class ReqeustHandler
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Request
 */
class RequestHandler
{

    /**
     * @var string
     */
    protected const GENERATOR_TOPOLOGY_URL = 'http://topology-api:80/api/topology/generate/{id}';

    /**
     * @var string
     */
    protected const RUN_TOPOLOGY_URL = 'http://topology-api:80/api/topology/run/{id}';

    /**
     * @var string
     */
    public const DELETE_TOPOLOGY_URL = 'http://topology-api:80/api/topology/delete/{id}';

    /**
     * @var string
     */
    public const TERMINATE_TOPOLOGY_URL = 'http://multi-counter-api:8005/topology/terminate/{id}';

    /**
     * @var string
     */
    public const INFO_TOPOLOGY_URL = 'http://topology-api:80/api/topology/info/{id}';

    /**
     * @var CurlManagerInterface
     */
    protected $curlManager;

    /**
     * ReqeustHandler constructor.
     *
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(CurlManagerInterface $curlManager)
    {
        $this->curlManager = $curlManager;
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function generateTopology(string $topologyId): ResponseDto
    {
        $uri = $this->getUrl($topologyId, self::GENERATOR_TOPOLOGY_URL);

        $dto      = new RequestDto('GET', new Uri($uri));
        $response = $this->curlManager->send($dto);

        return $response;
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function runTopology(string $topologyId): ResponseDto
    {
        $uri = $this->getUrl($topologyId, self::RUN_TOPOLOGY_URL);

        $dto      = new RequestDto('GET', new Uri($uri));
        $response = $this->curlManager->send($dto);

        return $response;
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function deleteTopology(string $topologyId): ResponseDto
    {
        $uri        = $this->getUrl($topologyId, self::DELETE_TOPOLOGY_URL);
        $counterUri = $this->getUrl($topologyId, self::TERMINATE_TOPOLOGY_URL);

        $dto = new RequestDto('GET', new Uri($counterUri));
        $dto->setHeaders([
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_DELETE_URL) => $uri,
        ]);

        $response = $this->curlManager->send($dto);

        return $response;
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function infoTopology(string $topologyId): ResponseDto
    {
        $uri = $this->getUrl($topologyId, self::INFO_TOPOLOGY_URL);

        $dto      = new RequestDto('GET', new Uri($uri));
        $response = $this->curlManager->send($dto);

        return $response;
    }

    /**
     * @param string $topologyId
     * @param string $url
     *
     * @return mixed
     */
    protected function getUrl(string $topologyId, string $url)
    {
        return str_replace('{id}', $topologyId, $url);
    }

}
