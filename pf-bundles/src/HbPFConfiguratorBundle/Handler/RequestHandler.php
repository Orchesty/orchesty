<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;

/**
 * Class RequestHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class RequestHandler
{

    public const DELETE_TOPOLOGY_URL    = 'http://topology-api:80/api/topology/delete/{id}';
    public const TERMINATE_TOPOLOGY_URL = 'http://multi-counter-api:8005/topology/terminate/{id}';
    public const INFO_TOPOLOGY_URL      = 'http://topology-api:80/api/topology/info/{id}';

    protected const RUN_TOPOLOGY_URL       = 'http://topology-api:80/api/topology/run/{id}';
    protected const GENERATOR_TOPOLOGY_URL = 'http://topology-api:80/api/topology/generate/{id}';

    protected const MULTI_PROBE_URL = 'http://multi-probe:8007/topology/status?topologyId={id}';

    protected const STARTING_POINT_URL = 'http://starting-point:80/topologies/{name}/invalidate-cache';

    /**
     * @var CurlManagerInterface
     */
    protected $curlManager;

    /**
     * RequestHandler constructor.
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
        $dto = new RequestDto(CurlManager::METHOD_GET, new Uri($uri));

        return $this->curlManager->send($dto);
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
        $dto = new RequestDto(CurlManager::METHOD_GET, new Uri($uri));

        return $this->curlManager->send($dto);
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

        $dto = new RequestDto(CurlManager::METHOD_GET, new Uri($counterUri));
        $dto->setHeaders([
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_DELETE_URL) => $uri,
        ]);

        return $this->curlManager->send($dto);
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
        $dto = new RequestDto(CurlManager::METHOD_GET, new Uri($uri));

        return $this->curlManager->send($dto);
    }

    /**
     * @param string $topologyId
     *
     * @return array
     * @throws CurlException
     */
    public function runTest(string $topologyId): array
    {
        $uri         = $this->getUrl($topologyId, self::MULTI_PROBE_URL);
        $requestDto  = new RequestDto(CurlManager::METHOD_GET, new Uri($uri));
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            return json_decode($responseDto->getBody(), TRUE);
        } else {
            throw new CurlException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }

    }

    /**
     * @param string $topologyName
     *
     * @return array
     * @throws CurlException
     */
    public function invalidateTopologyCache(string $topologyName): array
    {
        $uri         = $this->getUrl($topologyName, self::STARTING_POINT_URL, '{name}');
        $requestDto  = new RequestDto(CurlManager::METHOD_POST, new Uri($uri));
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            return json_decode($responseDto->getBody(), TRUE);
        } else {
            throw new CurlException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }

    }

    /**
     * @param string $topology
     * @param string $url
     * @param string $search
     *
     * @return mixed
     */
    protected function getUrl(string $topology, string $url, string $search = '{id}')
    {
        return str_replace($search, $topology, $url);
    }

}
