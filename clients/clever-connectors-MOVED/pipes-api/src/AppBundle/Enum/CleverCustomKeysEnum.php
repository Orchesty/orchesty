<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class CleverCustomKeysEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class CleverCustomKeysEnum extends EnumAbstract
{

    public const UNSUBSCRIBE = 'cm_unsubscribe';
    public const HARD_BOUNCE = 'cm_hard_bounce';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::UNSUBSCRIBE => 'cm_unsubscribe',
        self::HARD_BOUNCE => 'cm_hard_bounce',
    ];

    /**
     * @param string $eventType
     *
     * @return string
     * @throws CleverConnectorsException
     */
    public static function getFromType(string $eventType): string
    {
        switch ($eventType) {
            case SystemInstall::EVENT_UNSUBSCRIBE:
                return self::UNSUBSCRIBE;

            case SystemInstall::EVENT_HARD_BOUNCE:
                return self::HARD_BOUNCE;

            default:
                throw new CleverConnectorsException(
                    sprintf('Not valid option for field key [%s]', $eventType),
                    CleverConnectorsException::INVALID_ENUM_VALUE
                );
        }
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    public static function getType(string $fieldName): string
    {
        switch ($fieldName) {
            case self::UNSUBSCRIBE:
                return SystemInstall::EVENT_UNSUBSCRIBE;

            case self::HARD_BOUNCE:
                return SystemInstall::EVENT_HARD_BOUNCE;

            default:
                return '__NONE__';
        }
    }

}