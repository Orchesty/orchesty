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

    /**
     * @var string[]
     */
    protected static $choices = [
        self::FOREIGN_ID => 'foreign_id',
    ];

}