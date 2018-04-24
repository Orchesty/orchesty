<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 12.10.17
 * Time: 17:47
 */

namespace CleverConnectors\AppBundle\Enum;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class CleverFieldsEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class CleverFieldsEnum extends EnumAbstract
{

    public const FOREIGN_ID    = '_foreign_id';
    public const EMAIL         = 'email';
    public const FIRST_NAME    = 'first_name';
    public const LAST_NAME     = 'last_name';
    public const REACTIVATE    = 'reactivate';
    public const LISTS         = 'lists';
    public const SYSTEM_KEY    = 'system_key';
    public const UNSUBSCRIBE   = 'unsubscribe';
    public const HARD_BOUNCE   = 'hard_bounce';
    public const PLUGINS_LISTS = 'distribution_list';
    public const SEND_OPTIN    = 'send_optin';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::FOREIGN_ID    => '_foreign_id',
        self::EMAIL         => 'email',
        self::FIRST_NAME    => 'first_name',
        self::LAST_NAME     => 'last_name',
        self::REACTIVATE    => 'reactivate',
        self::LISTS         => 'lists',
        self::SYSTEM_KEY    => 'system_key',
        self::UNSUBSCRIBE   => 'unsubscribe',
        self::HARD_BOUNCE   => 'hard_bounce',
        self::PLUGINS_LISTS => 'distribution_list',
        self::SEND_OPTIN    => 'send_optin',
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
                    sprintf('Not valid option for field [%s]', $eventType),
                    CleverConnectorsException::INVALID_ENUM_VALUE
                );
        }
    }

}