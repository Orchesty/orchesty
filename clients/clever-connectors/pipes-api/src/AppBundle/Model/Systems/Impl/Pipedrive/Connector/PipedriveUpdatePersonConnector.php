<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

/**
 * Class PipedriveUpdatePersonConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveUpdatePersonConnector extends PipedrivePersonConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pipedrive-update-person-connector';
    }

}