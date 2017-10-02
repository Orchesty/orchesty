<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/2/17
 * Time: 10:58 AM
 */

namespace CleverConnectors\AppBundle\Model\Cron;

use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchActionInterface;
use InvalidArgumentException;
use JMS\Serializer\Serializer;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class CronBatchActionCallback
 *
 * @package CleverConnectors\AppBundle\Model\Cron
 */
class CronBatchActionCallback implements BatchActionInterface
{

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * CronBatchActionCallback constructor.
     *
     * @param Serializer $serializer
     */
    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
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
            return reject(new InvalidArgumentException('Body has not connector key.'));
        } else {
            return resolve($connectorKey);
        }
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
            ->parseBody($message->content)
            ->then(function (array $data) {
                return $this->getConnectorKey($data);
            })->then(function (string $connectorKey) use ($loop, $itemCallBack) {
                return $this->processSystem($loop, $connectorKey, $itemCallBack);
            });
    }

    /**
     * @param array $item
     * @param int   $i
     *
     * @return array
     */
    public function prepareData(array $item, int $i): array
    {
        return [
            'id'   => $i,
            'data' => $item,
        ];
    }

    /**
     * @param string   $chunk
     * @param int      $i
     * @param callable $itemCallback
     */
    private function processItem(string $chunk, int $i, callable $itemCallback): void
    {
        $this
            ->parseBody(trim($chunk))
            ->then(function (array $data) use ($i) {
                return $this->prepareData($data, $i);
            })
            ->then($itemCallback);
    }

    /**
     * @param LoopInterface $loop
     * @param string        $systemKey
     * @param callable      $itemCallback
     *
     * @return PromiseInterface
     */
    public function processSystem(LoopInterface $loop, string $systemKey, callable $itemCallback): PromiseInterface
    {
        $process = new Process(sprintf('bin/console react:get-system %s', $systemKey));
        $process->start($loop);

        $prom = new Promise(function ($resolve) use ($process, $itemCallback): void {

            $i = 0;
            $process->stdout->on('data', function (string $chunk) use (&$i, $itemCallback): void {
                $this->processItem($chunk, $i, $itemCallback);
                $i++;
            });

            $process->stdout->on('end', function () use ($resolve): void {
                $resolve();
            });

        }, function ($reject) use ($process): void {
            $process->stdout->on('error', function (Exception $e) use ($reject): void {
                $reject($e);
            });

            $process->on('exit', function ($exitCode, $termSignal) use ($reject): void {
                $reject(new RuntimeException(sprintf('Process exited with code %s', $exitCode)));
            });
        });

        return $prom;
    }

}