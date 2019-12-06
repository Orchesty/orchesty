<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode\Impl;

use Bunny\Channel;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Exception;
use Hanaboso\CommonsBundle\Database\Document\Embed\EmbedNode;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Database\Repository\NodeRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\GeneratorUtils;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Producer\AbstractProducer;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class RabbitCustomNode
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode\Impl
 */
abstract class RabbitCustomNode extends CustomNodeAbstract implements LoggerAwareInterface
{

    /**
     * @var AbstractProducer
     */
    private $producer;

    /**
     * @var ObjectRepository<Node>|NodeRepository
     */
    private $nodeRepo;

    /**
     * @var mixed[]
     */
    private $queues = [];

    /**
     * @var string
     */
    private $ex = '';

    /**
     * @var Channel
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
     * @throws LockException
     * @throws MappingException
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
     * @param mixed[] $message
     * @param mixed[] $headers
     */
    protected function publishMessage(array $message, array $headers): void
    {
        foreach ($this->queues as $que) {
            $this->chann->publish(Json::encode($message), $headers, $this->ex, $que);
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
     *
     * @throws LockException
     * @throws MappingException
     * @throws Exception
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
            $que            = GeneratorUtils::generateQueueNameFromStrings(
                (string) $topId,
                $next->getId(),
                $next->getName()
            );
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

        $this->queues = [];
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
