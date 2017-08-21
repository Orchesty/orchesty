<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 2:03 PM
 */

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Handler;

use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loader\ConnectorLoader;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Manager\ConnectorManager;

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
     * @param string   $id
     * @param string   $token
     * @param string[] $data
     *
     * @return string[]
     */
    public function processEvent(string $id, string $token, array $data): array
    {
        /** @var ConnectorInterface $conn */
        $conn = $this->loader->getConnector($id);
        $res  = $this->connManager->processEvent($conn, $data);

        return $res;
    }

}