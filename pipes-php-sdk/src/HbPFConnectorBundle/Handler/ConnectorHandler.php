<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler
 */
final class ConnectorHandler
{

    /**
     * ConnectorHandler constructor.
     *
     * @param ConnectorLoader $loader
     */
    function __construct(private ConnectorLoader $loader)
    {
    }

    /**
     * @param string $id
     *
     * @return void
     * @throws ConnectorException
     */
    public function processTest(string $id): void
    {
        $this->loader->getConnector($id);
    }

    /**
     * @param string  $id
     * @param Request $request
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(string $id, Request $request): ProcessDto
    {
        $conn = $this->loader->getConnector($id);

        return $conn->processAction(ProcessDtoFactory::createFromRequest($request));
    }

    /**
     * @return mixed[]
     */
    public function getConnectors(): array
    {
        return $this->loader->getAllConnectors();
    }

}
