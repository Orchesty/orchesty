<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class RabbitMqException
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Exception
 */
class RabbitMqException extends PipesFrameworkException
{

    public const MISSING_CALLBACK_DEFINITION  = 1;
    public const UNKNOWN_CALLBACK_STATUS_CODE = 10;

}
