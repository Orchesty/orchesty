<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class NotificationTypeEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class NotificationTypeEnum extends EnumAbstract
{

    public const NULL                = 'null';
    public const ACCESS_EXPIRATION   = 'access_expiration';
    public const DATA_ERROR          = 'data_error';
    public const SERVICE_UNAVAILABLE = 'service_unavailable';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::NULL,
        self::ACCESS_EXPIRATION,
        self::DATA_ERROR,
        self::SERVICE_UNAVAILABLE,
    ];

}
