<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\TestBenchmarkConnector;

use CleverConnectors\AppBundle\Model\CM\TestBenchmarkConnector\CMTestBenchmarkBatchGenerator;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;

/**
 * Created by PhpStorm.
 * User: lukas.hlavac
 * Date: 1/17/18
 * Time: 10:36 AM
 */
class CMTestBenchmarkBatchGeneratorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $count     = 10;
        $processed = 0;
        $loop      = Factory::create();
        $dto       = (new ProcessDto())->setData('{"count": ' . $count . '}');
        $conn      = new CMTestBenchmarkBatchGenerator();

        $process = $conn->processBatch($dto, $loop, function (SuccessMessage $message) use (&$processed): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));

            $processed++;
        });

        $process->done();
        $loop->run();

        $this->assertEquals($count, $processed);
    }

}