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

    public const FOREIGN_ID = '_foreign_id';
    public const EMAIL      = 'email';
    public const FIRST_NAME = 'first_name';
    public const LAST_NAME  = 'last_name';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::FOREIGN_ID => 'foreign_id',
        self::EMAIL      => 'email',
        self::FIRST_NAME => 'first_name',
        self::LAST_NAME  => 'last_name',
    ];

}