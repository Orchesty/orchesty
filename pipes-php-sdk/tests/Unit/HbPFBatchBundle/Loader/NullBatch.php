<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;

/**
 * Class NullBatch
 *
 * @package PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader
 */
final class NullBatch extends BatchAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return '0';
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
