<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Notification;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class NotificationException
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Notification
 */
final class NotificationException extends PipesFrameworkExceptionAbstract
{

    public const int NOTIFICATION_EXCEPTION = self::OFFSET + 1;

    protected const int OFFSET = 3_100;

}
