<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 11:13
 */

namespace Hanaboso\PipesFramework\Commons\RabbitMq\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class RabbitMqException
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq\Exception
 */
class RabbitMqException extends PipesFrameworkException
{

    /**
     * @var int
     */
    public const        MISSING_CALLBACK_DEFINITION  = 1;

    /**
     * @var int
     */
    public const        UNKNOWN_CALLBACK_STATUS_CODE = 10;

}
