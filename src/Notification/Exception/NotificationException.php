<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Notification\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class NotificationException
 *
 * @package Hanaboso\PipesFramework\Notification\Exception
 */
final class NotificationException extends PipesFrameworkException
{

    protected const OFFSET = 2900;

    public const NOTIFICATION_EXCEPTION = self::OFFSET + 1;

}