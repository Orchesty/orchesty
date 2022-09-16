<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Batch;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;

/**
 * Class TestNullBatch
 *
 * @package PipesPhpSdkTests\Unit\Batch
 */
final class TestNullBatch extends BatchAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null-test-trait';
    }

    /**
     * @param BatchProcessDto $dto
     *
     * @return BatchProcessDto
     */
    public function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        return $dto;
    }

}
