<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

/**
 * Class AmericaAimConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector
 */
final class AmericaAimConnector extends AimConnectorAbstract
{

    private const TYPE = 'america';

    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct(self::TYPE, $url);
    }

}
