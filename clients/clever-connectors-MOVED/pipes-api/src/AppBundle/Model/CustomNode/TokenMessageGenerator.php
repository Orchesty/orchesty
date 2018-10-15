<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stano
 * Date: 9.10.17
 * Time: 9:23
 */

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Model\Command\AsyncCommandFactory;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\all;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class RefreshTokensMessageGenerator
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
class TokenMessageGenerator implements BatchInterface, CustomNodeInterface
{

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AsyncCommandFactory
     */
    private $asyncCommandFactory;

    /**
     * CronBatchActionCallback constructor.
     *
     * @param Serializer          $serializer
     * @param AsyncCommandFactory $asyncCommandFactory
     */
    public function __construct(Serializer $serializer, AsyncCommandFactory $asyncCommandFactory)
    {
        $this->serializer          = $serializer;
        $this->asyncCommandFactory = $asyncCommandFactory;
        $this->logger              = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $content
     *
     * @return PromiseInterface
     */
    private function parseBody(string $content): PromiseInterface
    {
        try {
            return resolve($this->serializer->deserialize($content, 'array', 'json'));
        } catch (Exception $e) {
            return reject($e);
        }
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        return $this
            ->getExpiredSystems($loop)
            ->then(function (string $data) {
                return $this->parseBody($data);
            })->then(function (array $data) use ($callbackItem): Promise {
                $items = [];
                $i     = 0;
                foreach ($data as $item) {
                    $items[] = $this->processItem($item, $i, $callbackItem);
                    $i++;
                }

                return all($items);
            });
    }

    /**
     * @param array $item
     * @param int   $i
     *
     * @return PromiseInterface
     */
    public function prepareData(array $item, int $i): PromiseInterface
    {
        $message = new SuccessMessage($i);
        $message->setData(json_encode($item));

        return resolve($message);
    }

    /**
     * @param array    $item
     * @param int      $i
     * @param callable $itemCallback
     *
     * @return PromiseInterface
     */
    private function processItem(array $item, int $i, callable $itemCallback): PromiseInterface
    {
        return $this
            ->prepareData($item, $i)
            ->then($itemCallback);
    }

    /**
     * @param LoopInterface $loop
     *
     * @return Promise
     */
    private function getExpiredSystems(LoopInterface $loop): Promise
    {
        $this->logger->debug('Start finding system installs by expires');

        return $this->asyncCommandFactory
            ->create($loop, 'react:get-installs-before-expiration');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new RuntimeException('The process method is not implemented.');
    }

}