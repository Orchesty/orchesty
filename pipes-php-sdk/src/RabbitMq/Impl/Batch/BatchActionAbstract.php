<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Utils\Message;

/**
 * Class BatchActionAbstract
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
abstract class BatchActionAbstract implements BatchActionInterface, LoggerAwareInterface
{

    use BatchTrait;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * BatchActionAbstract constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param AMQPMessage $message
     * @param callable    $itemCallBack
     *
     * @return PromiseInterface
     */
    public function batchAction(AMQPMessage $message, callable $itemCallBack): PromiseInterface
    {
        return $this
            ->validateHeaders($message)
            ->then(fn(string $serviceName) => $this->getBatchService($serviceName))
            ->then(fn(BatchInterface $node) => $node->processBatch($this->createProcessDto($message), $itemCallBack));
    }

    /**
     * @param AMQPMessage $message
     *
     * @return PromiseInterface
     */
    private function validateHeaders(AMQPMessage $message): PromiseInterface
    {
        $headers = Message::getHeaders($message);

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_NAME, $headers))) {
            return new RejectedPromise(
                new InvalidArgumentException(sprintf('Missing "%s" in the message header.', PipesHeaders::NODE_NAME))
            );
        }

        $promise = $this->createPromise(static fn() => PipesHeaders::get(PipesHeaders::NODE_NAME, $headers));
        $promise->then(
            static fn() => PipesHeaders::get(PipesHeaders::NODE_NAME, $headers)
        );

        return $promise;
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    private function isEmpty(?string $value): bool
    {
        return $value === '' || $value === NULL;
    }

    /**
     * @param AMQPMessage $message
     *
     * @return ProcessDto
     */
    private function createProcessDto(AMQPMessage $message): ProcessDto
    {
        return ProcessDtoFactory::createFromMessage($message);
    }

}
