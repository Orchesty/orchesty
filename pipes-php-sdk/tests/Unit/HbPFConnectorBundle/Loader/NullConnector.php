<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;

/**
 * Class NullConnector
 *
 * @package PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader
 */
class NullConnector extends ConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return '0';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        return $dto;
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
