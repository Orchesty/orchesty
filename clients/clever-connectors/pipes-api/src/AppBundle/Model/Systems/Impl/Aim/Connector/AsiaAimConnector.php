<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;

/**
 * Class AsiaAimConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector
 */
final class AsiaAimConnector extends AimConnectorAbstract
{

    private const TYPE = 'asia';

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
