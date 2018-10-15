<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.11.17
 * Time: 14:54
 */

namespace CleverConnectors\AppBundle\Model\Mapper;

use CleverConnectors\AppBundle\Traits\StaticTrait;

/**
 * Class FieldKeyGenerator
 *
 * @package CleverConnectors\AppBundle\Model\Mapper
 */
final class FieldKeyGenerator
{

    use StaticTrait;

    public const DELIMITER = '.';

    /**
     * @param string $key
     *
     * @return array
     */
    public static function parseKey(string $key): array
    {
        $exploded = explode(self::DELIMITER, $key);

        return array_filter($exploded);
    }

}