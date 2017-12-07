<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Cron;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Exception\CronException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Commons\Utils\CronUtils;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Nette\Utils\Json;
use Throwable;

/**
 * Class CronManager
 *
 * @package Hanaboso\PipesFramework\Commons\Cron
 */
class CronManager
{

    private const CURL_COMMAND = 'curl -X POST %s%s';

    private const CREATE = '%s/cron-api/create';
    private const UPDATE = '%s/cron-api/update/%s';
    private const DELETE = '%s/cron-api/delete/%s';

    private const BATCH_CREATE = '%s/cron-api/batch_create';
    private const BATCH_UPDATE = '%s/cron-api/batch_update';
    private const BATCH_DELETE = '%s/cron-api/batch_delete';

    private const HASH    = 'hash';
    private const TIME    = 'time';
    private const COMMAND = 'command';

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var TopologyRepository|ObjectRepository
     */
    private $topologyRepository;

    /**
     * @var string
     */
    private $backend;

    /**
     * CronManager constructor.
     *
     * @param DocumentManager      $documentManager
     * @param CurlManagerInterface $curlManager
     * @param string               $backend
     */
    public function __construct(DocumentManager $documentManager, CurlManagerInterface $curlManager, string $backend)
    {
        $this->topologyRepository = $documentManager->getRepository(Topology::class);
        $this->curlManager        = $curlManager;
        $this->backend            = $backend;
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
     */
    public function create(Node $node): ResponseDto
    {
        $url = $this->getUrl(self::CREATE);
        $dto = (new RequestDto(CurlManager::METHOD_POST, $url))->setBody(Json::encode([
            'hash'    => $this->getHash($node),
            'time'    => $node->getCron(),
            'command' => $this->getCommand($node),
        ]));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
     */
    public function update(Node $node): ResponseDto
    {
        $url = $this->getUrl(self::UPDATE, $this->getHash($node));
        $dto = (new RequestDto(CurlManager::METHOD_POST, $url))->setBody(Json::encode([
            'time'    => $node->getCron(),
            'command' => $this->getCommand($node),
        ]));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node $node
     *
     * @return ResponseDto
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
     */
    public function batchUpdate(array $nodes): ResponseDto
    {
        $body = $this->processNodes($nodes);
        $dto  = (new RequestDto(CurlManager::METHOD_POST, $this->getUrl(self::BATCH_UPDATE)))->setBody($body);

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param Node[] $nodes
     *
     * @return ResponseDto
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
        $backend = rtrim($this->backend, '/');

        return new Uri($hash ? sprintf($url, $backend, $hash) : sprintf($url, $backend));
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    private function getHash(Node $node): string
    {
        $topology = $this->topologyRepository->findOneBy(['id' => $node->getTopology()]);

        return CronUtils::getHash($topology, $node);
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    private function getCommand(Node $node): string
    {
        $topology = $this->topologyRepository->findOneBy(['id' => $node->getTopology()]);

        return sprintf(self::CURL_COMMAND, rtrim($this->backend, '/'), CronUtils::getTopologyUrl($topology, $node));
    }

    /**
     * @param Node[] $nodes
     * @param array  $exclude
     *
     * @return string
     */
    private function processNodes(array $nodes, array $exclude = []): string
    {
        $processedNodes = [];

        foreach ($nodes as $node) {
            $processedNode[self::HASH] = $this->getHash($node);

            if (!in_array(self::TIME, $exclude, TRUE)) {
                $processedNode[self::TIME] = $node->getCron();
            }

            if (!in_array(self::COMMAND, $exclude, TRUE)) {
                $processedNode[self::COMMAND] = $this->getCommand($node);
            }

            $processedNodes[] = $processedNode;
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