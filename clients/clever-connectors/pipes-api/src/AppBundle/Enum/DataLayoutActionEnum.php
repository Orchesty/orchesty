<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class DataLayoutActionEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
class DataLayoutActionEnum extends EnumAbstract
{

    public const SUBSCRIBER = 'subscriber';
    public const CAMPAIGN   = 'campaign';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::SUBSCRIBER => 'subscriber',
        self::CAMPAIGN   => 'campaign',
    ];

}