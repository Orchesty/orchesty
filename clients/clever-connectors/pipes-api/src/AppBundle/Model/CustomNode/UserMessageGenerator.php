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
            })->then(function (string $connectorKey) use ($loop, $dto) {
                return all([
                    $this->processSystem($loop, $connectorKey),
                    $this->getTopology($loop, $dto->getHeader('node_id')),
                ]);
            })->then(function (array $result) {
                return all([
                    $this->parseBody($result[0]),
                    $this->parseBody($result[1]),
                ]);
            })->then(function (array $data) use ($callbackItem): Promise {
                $items = [];
                $i     = 0;
                foreach ($data[0] as $item) {
                    $items[] = $this->processItem($item, $data[1], $i, $callbackItem);
                    $i++;
                }

                return all($items);
            });
    }

    /**
     * @param array $item
     * @param array $topology
     * @param int   $i
     *
     * @return PromiseInterface
     */
    public function prepareData(array $item, array $topology, int $i): PromiseInterface
    {
        $message = new SuccessMessage($i);
        $message
            ->setData(json_encode([
                'topology'       => $topology,
                'system_install' => $item,
            ]))
            ->addHeaders('token', $item['token'] ?? '')
            ->addHeaders('guid', $item['user'] ?? '');

        return resolve($message);
    }

    /**
     * @param array    $item
     * @param array    $topology
     * @param int      $i
     * @param callable $itemCallback
     *
     * @return PromiseInterface
     * @internal param string $chunk
     */
    private function processItem(array $item, array $topology, int $i, callable $itemCallback): PromiseInterface
    {
        return $this
            ->prepareData($item, $topology, $i)
            ->then($itemCallback)
            ->then(function () use ($item): void {
                unset($item);
            });
    }

    /**
     * @param LoopInterface $loop
     * @param string        $systemKey
     *
     * @return PromiseInterface
     */
    private function processSystem(LoopInterface $loop, string $systemKey): PromiseInterface
    {
        $this->logger->info(sprintf('Start finding user system for key "%s".', $systemKey));

        return $this->asyncCommandFactory
            ->create($loop, sprintf('react:get-system %s', $systemKey))
            ->then(NULL, function (Exception $e) use ($systemKey) {
                $this->logger->error(sprintf('System [id=%s] not found', $systemKey));

                return reject($e);
            });
    }

    /**
     * @param LoopInterface $loop
     * @param string        $nodeId
     *
     * @return PromiseInterface
     */
    private function getTopology(LoopInterface $loop, string $nodeId): PromiseInterface
    {
        $this->logger->info(sprintf('Start finding topology by node id "%s".', $nodeId));

        return $this->asyncCommandFactory->create(
            $loop,
            sprintf('react:get-topology %s', $nodeId)
        )->then(NULL, function (Exception $e) use ($nodeId) {
            $this->logger->error(sprintf('Topology by nodeId [id=%s] not found.', $nodeId));

            return reject($e);
        });
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