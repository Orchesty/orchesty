<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

/**
 * Class AsiaAimConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector
 */
final class AsiaAimConnector extends AimConnectorAbstract
{

    private const TYPE = 'asia';

    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct(self::TYPE, $url);
    }

}
