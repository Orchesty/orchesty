<?php declare(strict_types=1);

namespace Tests\Unit\HbPFConnectorBundle\Loader;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class NullConnector
 *
 * @package Tests\Unit\HbPFConnectorBundle\Loader
 */
class NullConnector implements ConnectorInterface
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