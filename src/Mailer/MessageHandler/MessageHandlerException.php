<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\MessageHandler;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class MessageHandlerException
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageHandler
 */
class MessageHandlerException extends PipesFrameworkException
{

    protected const OFFSET = 500;

    public const INVALID_DATA = self::OFFSET + 1;

}
