<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;

/**
 * Class RequestHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class RequestHandler
{

    protected const BASE_TOPOLOGY_URL      = 'http://topology-api:80/api/topology/%s';
    protected const GENERATOR_TOPOLOGY_URL = 'http://topology-api:80/v1/api/topology/%s';
    protected const MULTI_PROBE_URL        = 'http://multi-probe:8007/topology/status?topologyId=%s';
    protected const STARTING_POINT_URL     = 'http://starting-point:80/topologies/%s/invalidate-cache';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CurlManagerInterface
     */
    protected $curlManager;

    /**
     * @var TopologyConfigFactory
     */
    private $configFactory;

    /**
     * RequestHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param CurlManagerInterface   $curlManager
     * @param TopologyConfigFactory  $configFactory
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        CurlManagerInterface $curlManager,
        TopologyConfigFactory $configFactory
    )
    {
        /** @var DocumentManager $dm */
        $dm                  = $dml->getDm();
        $this->dm            = $dm;
        $this->curlManager   = $curlManager;
        $this->configFactory = $configFactory;
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function generateTopology(string $topologyId): ResponseDto
    {
        /** @var NodeRepository $nodeRepository */
        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes          = $nodeRepository->getNodesByTopology($topologyId);

        $uri = sprintf(self::GENERATOR_TOPOLOGY_URL, $topologyId);
        $dto = new RequestDto(CurlManager::METHOD_POST, new Uri($uri));
        $dto->setBody($this->configFactory->create($nodes));

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
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $topologyId);
        $dto = new RequestDto(CurlManager::METHOD_PUT, new Uri($uri));
        $dto->setBody((string) json_encode(['action' => 'start']));

        return $this->curlManager->send($dto);
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function stopTopology(string $topologyId): ResponseDto
    {
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $topologyId);
        $dto = new RequestDto(CurlManager::METHOD_PUT, new Uri($uri));
        $dto->setBody((string) json_encode(['action' => 'stop']));

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
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $topologyId);
        $dto = new RequestDto(CurlManager::METHOD_DELETE, new Uri($uri));

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
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $topologyId);
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
        $uri         = sprintf(self::MULTI_PROBE_URL, $topologyId);
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
        $uri         = sprintf(self::STARTING_POINT_URL, $topologyName);
        $requestDto  = new RequestDto(CurlManager::METHOD_POST, new Uri($uri));
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            return json_decode($responseDto->getBody(), TRUE);
        } else {
            throw new CurlException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }
    }

}
