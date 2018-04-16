<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use Bunny\Channel;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;

/**
 * Class RabbitCustomNode
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
abstract class RabbitCustomNode implements CustomNodeInterface, LoggerAwareInterface
{

    /**
     * @var AbstractProducer
     */
    private $producer;

    /**
     * @var ObjectRepository|NodeRepository
     */
    private $nodeRepo;

    /**
     * @var array
     */
    private $queues = [];

    /**
     * @var string
     */
    private $ex = '';

    /**
     * @var Channel|PromiseInterface
     */
    private $chann;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * RabbitCustomNode constructor.
     *
     * @param DocumentManager  $dm
     * @param AbstractProducer $producer
     */
    public function __construct(DocumentManager $dm, AbstractProducer $producer)
    {
        $this->producer = $producer;
        $this->nodeRepo = $dm->getRepository(Node::class);
        $this->logger   = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->validate($dto);
        $this->normalizeHeaders($dto);
        $this->bindChannels($dto);
        $this->processBatch($dto);
        $this->unbindChannels();

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     */
    abstract protected function processBatch(ProcessDto $dto): void;

    /**
     * @param array $message
     * @param array $headers
     */
    protected function publishMessage(array $message, array $headers): void
    {
        foreach ($this->queues as $que) {
            $this->chann->publish(json_encode($message), $headers, $this->ex, $que);
        }
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------------------
     */

    /**
     * @param ProcessDto $dto
     */
    private function validate(ProcessDto $dto): void
    {
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_ID, $dto->getHeaders()))) {
            throw new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::NODE_ID))
            );
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $dto->getHeaders()))) {
            throw new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID))
            );
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::CORRELATION_ID, $dto->getHeaders()))) {
            throw new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::CORRELATION_ID))
            );
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::PROCESS_ID, $dto->getHeaders()))) {
            throw new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::PROCESS_ID))
            );
        }
        if (!array_key_exists(PipesHeaders::createKey(PipesHeaders::PARENT_ID), $dto->getHeaders())) {
            throw new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::PARENT_ID))
            );
        }

    }

    /**
     * @param ProcessDto $dto
     */
    private function normalizeHeaders(ProcessDto $dto): void
    {
        $headers = [];
        foreach ($dto->getHeaders() as $key => $header) {
            $headers[$key] = is_array($header) ? reset($header) : $header;
        }
        $dto->setHeaders($headers);
        unset($headers);
    }

    /**
     * @param ProcessDto $dto
     */
    private function bindChannels(ProcessDto $dto): void
    {
        $topId  = PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $dto->getHeaders());
        $nodeId = PipesHeaders::get(PipesHeaders::NODE_ID, $dto->getHeaders());

        $this->ex    = $this->producer->getExchange();
        $this->chann = $this->producer->getManager()->getChannel();

        /** @var Node $node */
        $node = $this->nodeRepo->find($nodeId);

        /** @var EmbedNode $next */
        foreach ($node->getNext() as $next) {
            $que            = GeneratorUtils::generateQueueNameFromStrings($topId, $next->getId(), $next->getName());
            $this->queues[] = $que;

            $this->chann->queueBind($que, $this->ex, $que);
        }
    }

    /**
     *
     */
    private function unbindChannels(): void
    {
        foreach ($this->queues as $que) {
            $this->chann->queueUnbind($que, $this->ex, $que);
        }
    }

    /**
     * @param null|string $value
     *
     * @return bool
     */
    private function isEmpty(?string $value): bool
    {
        return $value === '' || $value === NULL;
    }

}