<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;

/**
 * Class EuropeAimConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector
 */
final class EuropeAimConnector extends AimConnectorAbstract
{

    /**
     * @param AimSystem            $system
     * @param CurlManagerInterface $curl
     * @param string               $host
     */
    public function __construct(AimSystem $system, CurlManagerInterface $curl, string $host)
    {
        parent::__construct($system, $curl, AimSystem::DESTINATION_EUROPE, $host);
    }

}
