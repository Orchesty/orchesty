<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

/**
 * Class PipedriveDeletePersonConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveDeletePersonConnector extends PipedrivePersonConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pipedrive-delete-person-connector';
    }

}