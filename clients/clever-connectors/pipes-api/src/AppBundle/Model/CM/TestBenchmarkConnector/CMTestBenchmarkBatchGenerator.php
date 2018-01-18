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
use function React\Promise\resolve;

/**
 * Created by PhpStorm.
 * User: lukas.hlavac
 * Date: 1/16/18
 * Time: 6:10 PM
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

        for ($i = 1; $i <= $data['count']; $i++) {
            $callbackItem((new SuccessMessage(0))
                ->setData(
                    '{"BenchmarkTotal": ' . $data['count'] . ', "BenchmarkNumber": ' . $i . '}'
                )
            );
        }

        return resolve();
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