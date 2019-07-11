<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator;

use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Document\Node;
use Hanaboso\CommonsBundle\Repository\NodeRepository;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;

/**
 * Class TopologyGeneratorBridge
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator
 */
class TopologyGeneratorBridge
{

    public const MULTI_PROBE    = 'multi-probe';
    public const TOPOLOGY_API   = 'topology-api';
    public const STARTING_POINT = 'starting-point';

    protected const BASE_TOPOLOGY_URL      = 'http://%s/v1/api/topologies/%s';
    protected const GENERATOR_TOPOLOGY_URL = 'http://%s/v1/api/topologies/%s';
    protected const MULTI_PROBE_URL        = 'http://%s/topology/status?topologyId=%s';
    protected const STARTING_POINT_URL     = 'http://%s/topologies/%s/invalidate-cache';

    private const HEADERS = ['Content-Type' => 'application/json'];

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
     * @var array
     */
    private $configs;

    /**
     * TopologyGeneratorBridge constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param CurlManagerInterface   $curlManager
     * @param TopologyConfigFactory  $configFactory
     * @param array                  $configs
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        CurlManagerInterface $curlManager,
        TopologyConfigFactory $configFactory,
        array $configs
    )
    {
        /** @var DocumentManager $dm */
        $dm                  = $dml->getDm();
        $this->dm            = $dm;
        $this->curlManager   = $curlManager;
        $this->configFactory = $configFactory;
        $this->configs       = $configs;
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     * @throws TopologyConfigException
     */
    public function generateTopology(string $topologyId): ResponseDto
    {
        /** @var NodeRepository $nodeRepository */
        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes          = $nodeRepository->getNodesByTopology($topologyId);

        $uri = sprintf(self::GENERATOR_TOPOLOGY_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
        $dto = new RequestDto(CurlManager::METHOD_POST, new Uri($uri));
        $dto->setBody($this->configFactory->create($nodes))->setHeaders(self::HEADERS);

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
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
        $dto = new RequestDto(CurlManager::METHOD_PUT, new Uri($uri));
        $dto->setBody((string) json_encode(['action' => 'start']))->setHeaders(self::HEADERS);

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
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
        $dto = new RequestDto(CurlManager::METHOD_PUT, new Uri($uri));
        $dto->setBody((string) json_encode(['action' => 'stop']))->setHeaders(self::HEADERS);

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
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
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
        $uri = sprintf(self::BASE_TOPOLOGY_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
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
        $uri         = sprintf(self::MULTI_PROBE_URL, $this->configs[self::MULTI_PROBE], $topologyId);
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
        $uri         = sprintf(self::STARTING_POINT_URL, $this->configs[self::STARTING_POINT], $topologyName);
        $requestDto  = new RequestDto(CurlManager::METHOD_POST, new Uri($uri));
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            return json_decode($responseDto->getBody(), TRUE);
        } else {
            throw new CurlException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }
    }

}
