<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Mapper\Exception;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.11.17
 * Time: 11:18
 */

use Exception;

/**
 * Class MapperException
 *
 * @package CleverConnectors\AppBundle\Model\Mapper\Exception
 */
final class MapperException extends Exception
{

    public const PARSE_ERROR = 1;

}