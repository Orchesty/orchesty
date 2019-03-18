<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Handler;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\Connector\Model\ConnectorManager;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loader\ConnectorLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConnectorBundle\Handler
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
        /** @var ConnectorInterface $conn */
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
     * @return array
     */
    public function getConnectors(): array
    {
        return $this->loader->getAllConnectors();
    }

}
