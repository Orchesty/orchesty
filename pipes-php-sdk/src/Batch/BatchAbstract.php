<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Batch;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorTrait;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeTrait;

/**
 * Class BatchAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Batch
 */
abstract class BatchAbstract implements BatchInterface
{

    use CommonNodeTrait;
    use ConnectorTrait;

    /**
     * @param BatchProcessDto $dto
     *
     * @return BatchProcessDto
     */
    abstract function processAction(BatchProcessDto $dto): BatchProcessDto;

}
