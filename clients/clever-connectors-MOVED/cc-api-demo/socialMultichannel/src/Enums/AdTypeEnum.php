<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Enums;

use CleverApp\Enum\BaseEnum;

/**
 * Class AdTypeEnum
 *
 * @package CleverCore\SocialMultichannel\Enums
 */
class AdTypeEnum extends BaseEnum
{

    public const FB        = 'fb';
    public const INSTAGRAM = 'instagram';
    public const TWITTER   = 'twitter';

    /**
     * @var array
     */
    protected static $values = [
        self::FB,
        self::INSTAGRAM,
        self::TWITTER,
    ];

    /**
     * @var array
     */
    protected static $convert = [
        self::FB        => 0,
        self::INSTAGRAM => 1,
        self::TWITTER   => 2,
    ];

}