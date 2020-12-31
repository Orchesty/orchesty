<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector\Model\BatchConnector;

use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;

/**
 * Class BatchConnectorCallback
 *
 * @package Hanaboso\PipesPhpSdk\Connector\Model\BatchConnector
 */
final class BatchConnectorCallback extends BatchActionAbstract
{

    /**
     * @var ConnectorLoader
     */
    private ConnectorLoader $connectorLoader;

    /**
     * BatchConnectorCallback constructor.
     *
     * @param ConnectorLoader $connectorLoader
     */
    public function __construct(ConnectorLoader $connectorLoader)
    {
        $this->connectorLoader = $connectorLoader;
    }

    /**
     * @param string $id
     *
     * @return BatchInterface
     * @throws ConnectorException
     */
    public function getBatchService(string $id): BatchInterface
    {
        /** @var BatchInterface $connector */
        $connector = $this->connectorLoader->getConnector($id);

        return $connector;
    }

}
