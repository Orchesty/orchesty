<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class NotificationException
 *
 * @package Hanaboso\NotificationSender\Exception
 */
final class NotificationException extends PipesFrameworkExceptionAbstract
{

    public const NOTIFICATION_PARAMETER_NOT_FOUND = self::OFFSET + 1;
    public const NOTIFICATION_HANDLER_NOT_FOUND   = self::OFFSET + 2;
    public const NOTIFICATION_SENDER_NOT_FOUND    = self::OFFSET + 3;
    public const NOTIFICATION_EVENT_NOT_FOUND     = self::OFFSET + 4;
    public const NOTIFICATION_SETTINGS_NOT_FOUND  = self::OFFSET + 5;

    private const OFFSET = 100;

}