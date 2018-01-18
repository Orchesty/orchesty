<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\TestBenchmarkConnector;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Created by PhpStorm.
 * User: lukas.hlavac
 * Date: 1/17/18
 * Time: 11:32 AM
 */
class CMTestBenchmarkMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        $data['BenchmarkMapperPassed'] = 'true';

        return $dto->setData(json_encode($data));
    }

}