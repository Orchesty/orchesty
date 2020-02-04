<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;

/**
 * Class NullConnector
 *
 * @package PipesFrameworkTests
 */
class NullConnector extends ConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'null';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        return $dto->setData('{"key":"value"}');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        return $dto->setData('{"key":"value"}');
    }

}
