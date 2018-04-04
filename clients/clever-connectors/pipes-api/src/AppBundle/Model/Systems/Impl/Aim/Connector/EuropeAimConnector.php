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

    private const TYPE = 'europe';

    /**
     * @param AimSystem            $system
     * @param CurlManagerInterface $curl
     * @param string               $url
     */
    public function __construct(AimSystem $system, CurlManagerInterface $curl, string $url)
    {
        parent::__construct($system, $curl, self::TYPE, $url);
    }

}
