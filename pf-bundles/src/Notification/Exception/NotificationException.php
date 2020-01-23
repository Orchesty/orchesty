<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Notification\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class NotificationException
 *
 * @package Hanaboso\PipesFramework\Notification\Exception
 */
final class NotificationException extends PipesFrameworkExceptionAbstract
{

    public const NOTIFICATION_EXCEPTION = self::OFFSET + 1;

    protected const OFFSET = 2_900;

}
