<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Model\ConnectorManager;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler
 */
class ConnectorHandler
{

    /**
     * @var ConnectorManager
     */
    private $connManager;

    /**
     * @var ConnectorLoader
     */
    private $loader;

    /**
     * ConnectorHandler constructor.
     *
     * @param ConnectorManager $connManager
     * @param ConnectorLoader  $loader
     */
    function __construct(ConnectorManager $connManager, ConnectorLoader $loader)
    {
        $this->connManager = $connManager;
        $this->loader      = $loader;
    }

    /**
     * @param string  $id
     * @param Request $request
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(string $id, Request $request): ProcessDto
    {
        /** @var ConnectorInterface $conn */
        $conn = $this->loader->getConnector($id);

        return $this->connManager->processEvent($conn, $request);
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
        /** @var ConnectorInterface $conn */
        $conn = $this->loader->getConnector($id);

        return $this->connManager->processAction($conn, $request);
    }

    /**
     * @return mixed[]
     */
    public function getConnectors(): array
    {
        return $this->loader->getAllConnectors();
    }

}
