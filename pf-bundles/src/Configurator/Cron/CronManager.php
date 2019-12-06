<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Cron;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Database\Document\Topology;
use Hanaboso\CommonsBundle\Database\Repository\TopologyRepository;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesFramework\Configurator\Utils\CronUtils;
use stdClass;
use Throwable;

/**
 * Class CronManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Cron
 */
class CronManager
{

    private const CURL_COMMAND = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d \'{%s}\' %s%s';

    private const GET_ALL = '%s/cron-api/get_all';
    private const CREATE  = '%s/cron-api/create';
    private const UPDATE  = '%s/cron-api/update/%s';
    private const PATCH   = '%s/cron-api/patch/%s';
    private const DELETE  = '%s/cron-api/delete/%s';

    private const BATCH_CREATE = '%s/cron-api/batch_create';
    private const BATCH_UPDATE = '%s/cron-api/batch_update';
    private const BATCH_PATCH  = '%s/cron-api/batch_patch';
    private const BATCH_DELETE = '%s/cron-api/batch_delete';

    private const TOPOLOGY = 'topology';
    private const NODE     = 'node';
    private const TIME     = 'time';
    private const COMMAND  = 'command';

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var ObjectRepository<Topology>&TopologyRepository
     */
    private $topologyRepository;

    /**
     * @var string
     */
    private $backend;

    /**
     * @var string
     */
    private $cronHost;

    /**
     * CronManager constructor.
     *
     * @param DocumentManager      $documentManager
     * @param CurlManagerInterface $curlManager
     * @param string               $backend
     * @param string               $cronHost
     */
    public function __construct(
        DocumentManager $documentManager,
        CurlManagerInterface $curlManager,
        string $backend,
        string $cronHost
    )
    {
        $this->topologyRepository = $documentManager->getRepository(Topology::class);
        $this->curlManager        = $curlManager;
        $this->backend            = $backend;
        $this->cronHost           = $cronHost;
    }

    /**
     * @return ResponseDto
     * @throws CurlException
     * @throws CronException
     */
    public function getAll(): ResponseDto
    {
        return $this->sendAndProcessRequest(new RequestDto(CurlManager::METHOD_GET, $this->getUrl(self::GET_ALL)));
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function create(Node $node): ResponseDto
    {
        [$topologyName, $nodeName] = $this->getTopologyAndNode($node);
        $url = $this->getUrl(self::CREATE);
        $dto = (new RequestDto(CurlManager::METHOD_POST, $url))
            ->setBody(
                Json::encode(
                    [
                        'topology' => $topologyName,
                        'node'     => $nodeName,
                        'time'     => $node->getCron(),
                        'command'  => $this->getCommand($node),
                    ]
                )
            );

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function update(Node $node): ResponseDto
    {
        $url = $this->getUrl(self::UPDATE, $this->getHash($node));
        $dto = (new RequestDto(CurlManager::METHOD_POST, $url))
            ->setBody(
                Json::encode(
                    [
                        'time'    => $node->getCron(),
                        'command' => $this->getCommand($node),
                    ]
                )
            );

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node $node
     * @param bool $empty
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function patch(Node $node, bool $empty = FALSE): ResponseDto
    {
        $body = [
            'time'    => $node->getCron(),
            'command' => $this->getCommand($node),
        ];

        if ($empty) {
            $body = new stdClass();
        }

        $url = $this->getUrl(self::PATCH, $this->getHash($node));
        $dto = (new RequestDto(CurlManager::METHOD_POST, $url))
            ->setBody(Json::encode($body));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function delete(Node $node): ResponseDto
    {
        $url = $this->getUrl(self::DELETE, $this->getHash($node));
        $dto = new RequestDto(CurlManager::METHOD_POST, $url);

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node[] $nodes
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function batchCreate(array $nodes): ResponseDto
    {
        $body = $this->processNodes($nodes);
        $dto  = (new RequestDto(CurlManager::METHOD_POST, $this->getUrl(self::BATCH_CREATE)))->setBody($body);

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node[] $nodes
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function batchUpdate(array $nodes): ResponseDto
    {
        $body = $this->processNodes($nodes);
        $dto  = (new RequestDto(CurlManager::METHOD_POST, $this->getUrl(self::BATCH_UPDATE)))->setBody($body);

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node[] $nodes
     * @param bool   $empty
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function batchPatch(array $nodes, bool $empty = FALSE): ResponseDto
    {
        $exclude = [];
        if ($empty) {
            $exclude = [self::TIME, self::COMMAND];
        }

        $body = $this->processNodes($nodes, $exclude);
        $dto  = (new RequestDto(CurlManager::METHOD_POST, $this->getUrl(self::BATCH_PATCH)))->setBody($body);

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node[] $nodes
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     */
    public function batchDelete(array $nodes): ResponseDto
    {
        $body = $this->processNodes($nodes, [self::TIME, self::COMMAND]);
        $dto  = (new RequestDto(CurlManager::METHOD_POST, $this->getUrl(self::BATCH_DELETE)))->setBody($body);

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param string      $url
     * @param null|string $hash
     *
     * @return Uri
     */
    private function getUrl(string $url, ?string $hash = NULL): Uri
    {
        $backend = rtrim($this->cronHost, '/');

        return new Uri($hash ? sprintf($url, $backend, $hash) : sprintf($url, $backend));
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    private function getHash(Node $node): string
    {
        /** @var Topology $topology */
        $topology = $this->topologyRepository->findOneBy(['id' => $node->getTopology()]);

        return CronUtils::getHash($topology, $node);
    }

    /**
     * @param Node $node
     *
     * @return mixed[]
     */
    private function getTopologyAndNode(Node $node): array
    {
        /** @var Topology $topology */
        $topology = $this->topologyRepository->findOneBy(['id' => $node->getTopology()]);

        return [$topology->getName(), $node->getName()];
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    private function getCommand(Node $node): string
    {
        /** @var Topology $topology */
        $topology = $this->topologyRepository->findOneBy(['id' => $node->getTopology()]);

        return sprintf(
            self::CURL_COMMAND,
            $node->getCronParams(),
            rtrim($this->backend, '/'),
            CronUtils::getTopologyUrl($topology, $node)
        );
    }

    /**
     * @param Node[]  $nodes
     * @param mixed[] $exclude
     *
     * @return string
     */
    private function processNodes(array $nodes, array $exclude = []): string
    {
        $processedNodes = [];
        $processedNode  = [];

        foreach ($nodes as $node) {
            if ($node->getCron()) {
                [$processedNode[self::TOPOLOGY], $processedNode[self::NODE]] = $this->getTopologyAndNode($node);

                if (!in_array(self::TIME, $exclude, TRUE)) {
                    $processedNode[self::TIME] = $node->getCron();
                }

                if (!in_array(self::COMMAND, $exclude, TRUE)) {
                    $processedNode[self::COMMAND] = $this->getCommand($node);
                }

                $processedNodes[] = $processedNode;
            }
        }

        return Json::encode($processedNodes);
    }

    /**
     * @param RequestDto $dto
     *
     * @return ResponseDto
     * @throws CronException
     */
    private function sendAndProcessRequest(RequestDto $dto): ResponseDto
    {
        $dto->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ]
        );

        try {
            return $this->curlManager->send($dto);
        } catch (Throwable $e) {
            throw new CronException(
                sprintf('Cron API failed: %s', $e->getMessage()),
                CronException::CRON_EXCEPTION
            );
        }
    }

}
