<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class MapperException
 *
 * @package Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception
 */
class MapperException extends PipesFrameworkExceptionAbstract
{

    public const MAPPER_NOT_EXIST = self::OFFSET + 1;

    protected const OFFSET = 1_700;

}
