<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class SystemUITypeEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class SystemUITypeEnum extends EnumAbstract
{

    public const BASIC  = 'basic';
    public const MAPPER = 'mapper';
    public const ADVERT = 'advert';
    public const NO_UI  = 'no_ui';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::BASIC  => self::BASIC,
        self::MAPPER => self::MAPPER,
        self::ADVERT => self::ADVERT,
        self::NO_UI  => self::NO_UI,
    ];

}