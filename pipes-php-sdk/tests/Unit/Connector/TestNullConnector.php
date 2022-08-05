<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;

/**
 * Class TestNullConnector
 *
 * @package PipesPhpSdkTests\Unit\Connector
 */
final class TestNullConnector extends ConnectorAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null-test-trait';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto;

        return new ProcessDto();
    }

}
