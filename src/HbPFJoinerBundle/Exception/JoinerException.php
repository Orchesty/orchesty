<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:44 PM
 */

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class JoinerException
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Exception
 */
final class JoinerException extends PipesFrameworkException
{

    protected const OFFSET = 1600;

    public const JOINER_SERVICE_NOT_FOUND = self::OFFSET + 1;
    public const MISSING_DATA_IN_REQUEST  = self::OFFSET + 2;

}