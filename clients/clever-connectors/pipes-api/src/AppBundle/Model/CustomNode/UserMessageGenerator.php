<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 13:59
 */

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Model\Command\AsyncCommandFactory;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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
    private function getSystemKey(array $data): PromiseInterface
    {
        $connectorKey = $data['param'] ?? NULL;

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
                return $this->getSystemKey($data);
            })->then(function (string $systemKey) use ($loop) {
                return $this->getSystems($loop, $systemKey);
            })->then(function (string $data) {
                return $this->parseBody($data);
            })->then(function (array $data) use ($callbackItem): Promise {
                $items = [];
                $i     = 1;
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
            ->setData(json_encode([
                'system_install' => $item,
            ]))
            ->addHeader(CMHeaders::createKey(CMHeaders::TOKEN), $item['token'] ?? '')
            ->addHeader(CMHeaders::createKey(CMHeaders::GUID), $item['user'] ?? '')
            ->addHeader(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), $item['system'] ?? '');

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
    private function getSystems(LoopInterface $loop, string $systemKey): PromiseInterface
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
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new RuntimeException('The process method is not implemented.');
    }

}