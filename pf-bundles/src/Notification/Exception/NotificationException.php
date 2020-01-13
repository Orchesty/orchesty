<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Notification\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class NotificationException
 *
 * @package Hanaboso\PipesFramework\Notification\Exception
 */
final class NotificationException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 2_900;

    public const NOTIFICATION_EXCEPTION = self::OFFSET + 1;

}
