<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class AdTypeEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class AdTypeEnum extends EnumAbstract
{

    public const FB        = 'fb';
    public const INSTAGRAM = 'instagram';
    public const TWITTER   = 'twitter';

    /**
     * @var array
     */
    protected static $choices = [
        self::FB        => self::FB,
        self::INSTAGRAM => self::INSTAGRAM,
        self::TWITTER   => self::TWITTER,
    ];

}