<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\MessageHandler;

use RuntimeException;

/**
 * Class MessageHandlerException
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageHandler
 */
class MessageHandlerException extends RuntimeException
{

    public const INVALID_DATA = 1;

}
