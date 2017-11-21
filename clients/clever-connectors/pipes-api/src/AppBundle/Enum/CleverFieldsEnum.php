<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 12.10.17
 * Time: 17:47
 */

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class CleverFieldsEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class CleverFieldsEnum extends EnumAbstract
{

    public const FOREIGN_ID  = '_foreign_id';
    public const EMAIL       = 'email';
    public const FIRST_NAME  = 'first_name';
    public const LAST_NAME   = 'last_name';
    public const REACTIVATE  = 'reactivate';
    public const LISTS       = 'lists';
    public const SYSTEM_KEY  = 'system_key';
    public const UNSUBSCRIBE = 'unsubscribe';
    public const HARD_BOUNCE = 'hard_bounce';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::FOREIGN_ID  => '_foreign_id',
        self::EMAIL       => 'email',
        self::FIRST_NAME  => 'first_name',
        self::LAST_NAME   => 'last_name',
        self::REACTIVATE  => 'reactivate',
        self::LISTS       => 'lists',
        self::SYSTEM_KEY  => 'system_key',
        self::UNSUBSCRIBE => 'unsubscribe',
        self::HARD_BOUNCE => 'hard_bounce',
    ];

}