<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Utils\Message;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class BatchActionAbstract
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
abstract class BatchActionAbstract implements BatchActionInterface, LoggerAwareInterface
{

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
     *
     * @return PromiseInterface
     */
    private function validateHeaders(AMQPMessage $message): PromiseInterface
    {
        $headers = Message::getHeaders($message);

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_NAME, $headers))) {
            return reject(
                new InvalidArgumentException(sprintf('Missing "%s" in the message header.', PipesHeaders::NODE_NAME))
            );
        }

        return resolve(PipesHeaders::get(PipesHeaders::NODE_NAME, $headers));
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

    /**
     * @param AMQPMessage   $message
     * @param LoopInterface $loop
     * @param callable      $itemCallBack
     *
     * @return PromiseInterface
     */
    public function batchAction(AMQPMessage $message, LoopInterface $loop, callable $itemCallBack): PromiseInterface
    {
        return $this
            ->validateHeaders($message)
            ->then(fn(string $serviceName) => $this->getBatchService($serviceName))
            ->then(
                fn(BatchInterface $node) => $node->processBatch($this->createProcessDto($message), $loop, $itemCallBack)
            );
    }

}
