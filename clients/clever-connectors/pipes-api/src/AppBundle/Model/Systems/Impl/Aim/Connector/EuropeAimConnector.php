<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

/**
 * Class EuropeAimConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector
 */
final class EuropeAimConnector extends AimConnectorAbstract
{

    private const TYPE = 'europe';

    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct(self::TYPE, $url);
    }

}
