<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMapperBundle\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class MapperException
 *
 * @package Hanaboso\PipesFramework\HbPFMapperBundle\Exception
 */
class MapperException extends PipesFrameworkException
{

    protected const OFFSET = 1700;

    public const MAPPER_NOT_EXIST = self::OFFSET + 1;

}