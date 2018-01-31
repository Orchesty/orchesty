<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\TestBenchmarkConnector;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\map;
use function React\Promise\resolve;

/**
 * Class CMTestBenchmarkBatchGenerator
 *
 * @package CleverConnectors\AppBundle\Model\CM\TestBenchmarkConnector
 */
class CMTestBenchmarkBatchGenerator implements BatchInterface, CustomNodeInterface, LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!$data['count']) {
            $this->logger->error("CMTestBenchmarkBatchGenerator: Count param is missing");

            throw new RuntimeException('Count param is missing');
        }

        $i = 0;

        return $this->send((int) $data['count'], $callbackItem, $i);
    }

    /**
     * @param int      $count
     * @param callable $callback
     * @param int      $i
     *
     * @return PromiseInterface|mixed
     */
    private function send(int $count, callable $callback, int &$i)
    {
        return resolve()
            ->then(function () use ($count, &$i): array {

                $max = 1000;
                if ($count < 1000) {
                    $max = $count;
                }

                if (($count - $i) < $max) {
                    $max = $count - $i;
                }

                $msgs = [];
                for ($j = 0; ($j < $max); $j++) {
                    $msg = new SuccessMessage($i);
                    $msg->setData('{"BenchmarkTotal": ' . $count . ', "BenchmarkNumber": ' . $i . '}');
                    $msgs[] = $msg;
                    $i++;
                }

                return $msgs;
            })
            ->then(function ($msgs) use ($callback) {
                return map($msgs, $callback);
            })
            ->then(function () use ($count, $callback, $i): PromiseInterface {

                if ($count <= $i) {
                    return resolve();
                } else {
                    return $this->send($count, $callback, $i);
                }
            });
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

}