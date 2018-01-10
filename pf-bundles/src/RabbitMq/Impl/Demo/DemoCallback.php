<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 31.8.17
 * Time: 14:25
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Demo;

use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;

/**
 * Class DemoCallback
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Demo
 */
class DemoCallback extends SyncCallbackAbstract
{

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    function handle($data, Message $message): CallbackStatus
    {
        if (empty($data)) {

            return new CallbackStatus(CallbackStatus::RESEND);
        } else {

            return new CallbackStatus(CallbackStatus::SUCCESS);
        }
    }

}
