<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;

/**
 * Class NullConnector
 *
 * @package PipesFrameworkTests
 */
final class NullConnector extends ConnectorAbstract
{

    public const NAME = 'null';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
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
