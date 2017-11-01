<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

/**
 * Class PipedriveDeletedPersonConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveDeletedPersonConnector extends PipedriveWebhookPersonConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pipedrive-deleted-person-connector';
    }

}