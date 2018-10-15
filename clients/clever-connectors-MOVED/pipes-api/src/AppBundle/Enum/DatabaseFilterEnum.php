<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class DatabaseFilterEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class DatabaseFilterEnum extends EnumAbstract
{

    public const DELETED = 'deleted';
    /**
     * @var string[]
     */
    protected static $choices = [
        self::DELETED => 'deleted',
    ];

}
