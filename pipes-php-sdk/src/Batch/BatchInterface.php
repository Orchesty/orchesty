<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Batch;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Interface BatchInterface
 *
 * @package Hanaboso\PipesPhpSdk\Batch
 */
interface BatchInterface
{

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param BatchProcessDto $dto
     *
     * @return BatchProcessDto
     * @throws ConnectorException
     */
    public function processAction(BatchProcessDto $dto): BatchProcessDto;

}
