<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;
use Hanaboso\PipesFramework\Configurator\Repository\ApiTokenRepository;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\LoggerTrait;
use JsonException;
use Psr\Log\NullLogger;

/**
 * Class TopologyGeneratorBridge
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator
 */
final class TopologyGeneratorBridge
{

    use LoggerTrait;

    public const TOPOLOGY_API   = 'topology-api';
    public const STARTING_POINT = 'starting-point';
    public const LIMITER        = 'limiter';

    protected const BASE_TOPOLOGY_URL      = 'http://%s/v1/api/topologies/%s';
    protected const GET_TOPOLOGY_HOST_URL  = 'http://%s/v1/api/topologies/%s/host';
    protected const GENERATOR_TOPOLOGY_URL = 'http://%s/v1/api/topologies/%s';
    protected const STARTING_POINT_URL     = '%s/topologies/%s/invalidate-cache';
    protected const LIMITER_URL            = '%s/terminate/topology-api/%s';

    private const HEADERS = ['Content-Type' => 'application/json'];

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var ApiTokenRepository
     */
    private ApiTokenRepository $apiTokenRepository;

    /**
     * TopologyGeneratorBridge constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param CurlManagerInterface   $curlManager
     * @param TopologyConfigFactory  $configFactory
     * @param mixed[]                $configs
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        protected CurlManagerInterface $curlManager,
        private TopologyConfigFactory $configFactory,
        private array $configs,
    )
    {
        $this->logger = new NullLogger();
        /** @var DocumentManager $dm */
        $dm                       = $dml->getDm();
        $this->dm                 = $dm;
        $this->apiTokenRepository = $dm->getRepository(ApiToken::class);
    }

    /**
     * @param string $topologyId
     *
     * @return ResponseDto
     * @throws CurlException
     * @throws TopologyConfigException
     * @throws LockException
     * @throws MappingException
     * @throws JsonException
     */
    public function generateTopology(string $topologyId): ResponseDto
    {
        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes          = $nodeRepository->getNodesByTopology($topologyId);

        $uri = sprintf(self::GENERATOR_TOPOLOGY_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
        $dto = new RequestDto(new Uri($uri), CurlManager::METHOD_POST, new ProcessDto());
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
        $dto = new RequestDto(new Uri($uri), CurlManager::METHOD_PUT, new ProcessDto());
        $dto->setBody(Json::encode(['action' => 'start']))->setHeaders(self::HEADERS);

        return $this->curlManager->send($dto);
    }

    /**
     * @param string $topologyId
     * @param bool   $deleteQueues
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function stopTopology(string $topologyId, bool $deleteQueues = FALSE): ResponseDto
    {
        try {
            $this->callTopologyBridge($topologyId, CurlManager::METHOD_DELETE, $deleteQueues ? 'api/destroy' : 'close');
        } catch (Exception $e) {
            $this->logger->warning(sprintf('Calling bridge: %s', $e->getMessage()));
            // Ignore and continue to shut bridge down
        }

        $uri = sprintf(self::BASE_TOPOLOGY_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
        $dto = new RequestDto(new Uri($uri), CurlManager::METHOD_PUT, new ProcessDto());
        $dto->setBody(Json::encode(['action' => 'stop']))->setHeaders(self::HEADERS);

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
        $dto = new RequestDto(new Uri($uri), CurlManager::METHOD_DELETE, new ProcessDto());

        return $this->curlManager->send($dto);
    }

    /**
     * @param string $topologyName
     *
     * @return mixed[]
     * @throws CurlException
     */
    public function invalidateTopologyCache(string $topologyName): array
    {
        $uri         = sprintf(self::STARTING_POINT_URL, $this->configs[self::STARTING_POINT], $topologyName);
        $requestDto  = new RequestDto(new Uri($uri), CurlManager::METHOD_POST, new ProcessDto());
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            return Json::decode($responseDto->getBody());
        } else {
            throw new CurlException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }
    }

    /**
     * @param string  $topologyId
     * @param mixed[] $headers
     *
     * @return mixed[]
     * @throws CurlException
     */
    public function removeAllLimiterAndRepeaterMessages(string $topologyId, array $headers): array
    {
        $uri         = sprintf(self::LIMITER_URL, $this->configs[self::LIMITER], $topologyId);
        $requestDto  = new RequestDto(new Uri($uri), CurlManager::METHOD_DELETE, new ProcessDto(), '', $headers);
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            return Json::decode($responseDto->getBody());
        } else {
            throw new CurlException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }
    }

    /**
     * ------------------------------- HELPERS -------------------------------
     */

    /**
     * @param string $topologyId
     * @param string $method
     * @param string $uriPath
     *
     * @return mixed[]
     * @throws CurlException
     */
    private function callTopologyBridge(string $topologyId, string $method, string $uriPath): array
    {
        $uri         = sprintf(self::GET_TOPOLOGY_HOST_URL, $this->configs[self::TOPOLOGY_API], $topologyId);
        $requestDto  = new RequestDto(new Uri($uri), CurlManager::METHOD_GET, new ProcessDto());
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            $headers = [];
            if (str_starts_with($uriPath, 'api/')) {
                $headers['orchesty-api-key'] = $this->apiTokenRepository
                    ->findOneBy(['user' => ApplicationController::SYSTEM_USER])?->getKey() ?? '';
            }

            $res        = Json::decode($responseDto->getBody());
            $host       = $res['host'] ?? '';
            $requestDto = new RequestDto(
                new Uri(sprintf('%s/%s', $host, $uriPath)),
                $method,
                new ProcessDto(),
                '',
                $headers,
            );

            $responseDto = $this->curlManager->send($requestDto);

            if ($responseDto->getStatusCode() === 200) {
                return Json::decode($responseDto->getBody());
            }
        }

        throw new CurlException(
            sprintf('Request error: %s, %s', $responseDto->getReasonPhrase(), $responseDto->getBody()),
        );
    }

}
