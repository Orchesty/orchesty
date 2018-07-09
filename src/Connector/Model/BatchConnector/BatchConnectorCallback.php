<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 16:41
 */

namespace Hanaboso\PipesFramework\Connector\Model\BatchConnector;

use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loader\ConnectorLoader;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchActionAbstract;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use InvalidArgumentException;

/**
 * Class BatchConnectorCallback
 *
 * @package Hanaboso\PipesFramework\Connector\Model\BatchConnector
 */
class BatchConnectorCallback extends BatchActionAbstract
{

    /**
     * @var ConnectorLoader
     */
    private $connectorLoader;

    /**
     * CronBatchActionCallback constructor.
     *
     * @param ConnectorLoader $connectorLoader
     */
    public function __construct(ConnectorLoader $connectorLoader)
    {
        parent::__construct();
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

        if (!$connector instanceof BatchInterface) {
            throw new InvalidArgumentException(sprintf('The connector not implemented "%s".', BatchInterface::class));
        }

        return $connector;
    }

}