<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

/**
 * Class PipedriveUpdatedPersonConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveUpdatedPersonConnector extends PipedriveWebhookPersonConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pipedrive-updated-person-connector';
    }

}