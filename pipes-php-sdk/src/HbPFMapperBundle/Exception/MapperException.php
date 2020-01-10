<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class MapperException
 *
 * @package Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception
 */
class MapperException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1_700;

    public const MAPPER_NOT_EXIST = self::OFFSET + 1;

}