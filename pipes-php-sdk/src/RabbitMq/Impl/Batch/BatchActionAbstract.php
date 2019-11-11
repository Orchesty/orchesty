<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Bunny\Message;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
     * @param Message $message
     *
     * @return PromiseInterface
     */
    private function validateHeaders(Message $message): PromiseInterface
    {
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_NAME, $message->headers))) {
            return reject(
                new InvalidArgumentException(
                    sprintf('Missing "%s" in the message header.', PipesHeaders::NODE_NAME)
                )
            );
        }

        return resolve(PipesHeaders::get(PipesHeaders::NODE_NAME, $message->headers));
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

    /**
     * @param Message $message
     *
     * @return ProcessDto
     */
    private function createProcessDto(Message $message): ProcessDto
    {
        return (new ProcessDto())
            ->setHeaders($message->headers)
            ->setData($message->content);
    }

    /**
     * @param Message       $message
     * @param LoopInterface $loop
     * @param callable      $itemCallBack
     *
     * @return PromiseInterface
     */
    public function batchAction(Message $message, LoopInterface $loop, callable $itemCallBack): PromiseInterface
    {
        return $this
            ->validateHeaders($message)
            ->then(
                function (string $serviceName) {
                    return $this->getBatchService($serviceName);
                }
            )->then(
                function (BatchInterface $node) use ($message, $loop, $itemCallBack) {
                    return $node->processBatch($this->createProcessDto($message), $loop, $itemCallBack);
                }
            );
    }

}
