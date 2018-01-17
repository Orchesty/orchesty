<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\TestBenchmarkConnector;

use CleverConnectors\AppBundle\Model\CM\TestBenchmarkConnector\CMTestBenchmarkMapper;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\KernelTestCaseAbstract;

/**
 * Created by PhpStorm.
 * User: lukas.hlavac
 * Date: 1/17/18
 * Time: 11:35 AM
 */
class CMTestBenchmarkMapperTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $data          = '{"foo":"bar"}';
        $data_expected = '{"foo":"bar","BenchmarkMapperPassed":"true"}';

        $conn = new CMTestBenchmarkMapper();
        $res  = $conn->process((new ProcessDto())->setData($data));

        $this->assertEquals($data_expected, $res->getData());
    }

}