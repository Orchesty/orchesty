<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Enums;

use CleverApp\Enum\BaseEnum;

/**
 * Class AudienceSourceEnum
 *
 * @package CleverCore\SocialMultichannel\Enums
 */
class AudienceSourceEnum extends BaseEnum
{

    public const LIST    = 'list';
    public const SEGMENT = 'segment';

    /**
     * @var array
     */
    protected static $values = [
        self::LIST,
        self::SEGMENT,
    ];

    /**
     * @var array
     */
    protected static $convert = [
        self::LIST        => 0,
        self::SEGMENT => 1,
    ];

}