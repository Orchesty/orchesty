<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 17:20
 */

namespace CleverConnectors\AppBundle\Utils;

use CleverConnectors\AppBundle\Traits\StaticTrait;
use LogicException;

/**
 * Class TopologyNameUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class TopologyNameUtils
{

    use StaticTrait;

    //Service-topologies
    public const REFRESH_TOKEN  = 'refresh-token';
    public const ACTIVATE_EVENT = 'activate-event';

    //Systems-topologies
    public const SYNC                 = 'sync-subscribers';
    public const CREATED_SUBSCRIBERS  = 'created-subscribers';
    public const UPDATED_SUBSCRIBERS  = 'updated-subscribers';
    public const DELETED_SUBSCRIBERS  = 'deleted-subscribers';
    public const VALIDATE_SUBSCRIBERS = 'validate-subscribers';

    public const CREATE_CONTACT      = 'create-contact';
    public const UPDATE_CONTACT      = 'update-contact';
    public const UNSUBSCRIBE_CONTACT = 'unsubscribe-contact';
    public const HARD_BOUNCE_CONTACT = 'hard-bounce-contact';
    public const SUBSCRIBE_CONTACT   = 'subscribe-contact';

    public const SWITCH_TOKEN = 'switch-token';
    public const GET_LIMIT    = 'get-limit';

    //SocialMultichannel
    public const CREATE_AD       = 'create-ad';
    public const CREATE_AUDIENCE = 'create-audience';
    public const UPDATE_AUDIENCE = 'update-audience';
    public const CHECK_STATUS    = 'check-status';

    /**
     * @var array
     */
    private static $service = [
        self::REFRESH_TOKEN,
        self::ACTIVATE_EVENT,
    ];

    /**
     * @var array
     */
    private static $system = [
        self::SYNC,
        self::CREATED_SUBSCRIBERS,
        self::UPDATED_SUBSCRIBERS,
        self::DELETED_SUBSCRIBERS,
        self::VALIDATE_SUBSCRIBERS,
        self::CREATE_CONTACT,
        self::UPDATE_CONTACT,
        self::UNSUBSCRIBE_CONTACT,
        self::HARD_BOUNCE_CONTACT,
        self::SUBSCRIBE_CONTACT,
        self::SWITCH_TOKEN,
        self::GET_LIMIT,
        self::CREATE_AD,
        self::CREATE_AUDIENCE,
        self::UPDATE_AUDIENCE,
        self::CHECK_STATUS,
    ];

    /**
     * @param string $const
     * @param string $systemKey
     * @param string $user
     *
     * @return string
     */
    public static function getServiceTopologyName(string $const, string $systemKey = '', string $user = ''): string
    {
        if (!in_array($const, self::$service)) {
            throw new LogicException(sprintf('Const "%s" is not a valid const for service topology!', $const));
        }

        if ($systemKey && $user) {
            return sprintf('%s-%s-%s', $user, $systemKey, $const);
        }

        if ($systemKey) {
            return sprintf('%s-%s', $systemKey, $const);
        }

        return $const;
    }

    /**
     * @param string $const
     * @param string $systemKey
     * @param string $user
     *
     * @return string
     */
    public static function getTopologyName(string $const, string $systemKey, string $user = ''): string
    {
        if (!in_array($const, self::$system)) {
            throw new LogicException(sprintf('Const "%s" is not a valid const for system topology!', $const));
        }

        if ($user) {
            return sprintf('%s-%s-%s', $user, $systemKey, $const);
        }

        return sprintf('%s-%s', $systemKey, $const);
    }

}