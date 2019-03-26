<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMapperBundle\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class MapperException
 *
 * @package Hanaboso\PipesFramework\HbPFMapperBundle\Exception
 */
class MapperException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1700;

    public const MAPPER_NOT_EXIST = self::OFFSET + 1;

}