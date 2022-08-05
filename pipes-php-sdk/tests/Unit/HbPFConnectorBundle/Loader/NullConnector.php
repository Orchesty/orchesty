<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;

/**
 * Class NullConnector
 *
 * @package PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader
 */
final class NullConnector extends ConnectorAbstract
{

    /**
     * @return string
     */
    function getName(): string
    {
        return 'null-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

}
