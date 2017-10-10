<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 13:59
 */

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Model\Command\AsyncCommandFactory;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use InvalidArgumentException;
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
 * Class UserMessageGenerator
 *
 * @package AppBundle\Model\CustomNode
 */
class UserMessageGenerator implements BatchInterface, CustomNodeInterface
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
     *
     * @internal param string $projectDir
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
     * @param array $data
     *
     * @return PromiseInterface
     */
    private function getConnectorKey(array $data): PromiseInterface
    {
        $connectorKey = $data['data']['param'] ?? NULL;

        if ($connectorKey === NULL) {
            return reject(new InvalidArgumentException('Body has not system key.'));
        } else {
            return resolve($connectorKey);
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
            ->parseBody($dto->getData())
            ->then(function (array $data) {
                return $this->getConnectorKey($data);
            })->then(function (string $connectorKey) use ($loop) {
                return $this->processSystem($loop, $connectorKey);
            })->then(function (string $data) {
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
        $message
            ->setData(json_encode($item))
            ->addHeaders('token', $item['token'] ?? '')
            ->addHeaders('guid', $item['user'] ?? '');

        return resolve($message);
    }

    /**
     * @param array    $item
     * @param int      $i
     * @param callable $itemCallback
     *
     * @return PromiseInterface
     * @internal param string $chunk
     */
    private function processItem(array $item, int $i, callable $itemCallback): PromiseInterface
    {
        return $this
            ->prepareData($item, $i)
            ->then($itemCallback);
    }

    /**
     * @param LoopInterface $loop
     * @param string        $systemKey
     *
     * @return Promise
     */
    private function processSystem(LoopInterface $loop, string $systemKey): Promise
    {
        $this->logger->info(sprintf('Start finding user system for key "%s".', $systemKey));

        return $this->asyncCommandFactory->create($loop, sprintf('react:get-system %s', $systemKey));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new RuntimeException('The process method is not implemented.');
    }

}