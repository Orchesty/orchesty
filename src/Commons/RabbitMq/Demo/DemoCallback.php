<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 31.8.17
 * Time: 14:25
 */

namespace Hanaboso\PipesFramework\Commons\RabbitMq\Demo;

use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\BaseCallbackAbstract;
use Hanaboso\PipesFramework\Commons\RabbitMq\CallbackStatus;

/**
 * Class DemoCallback
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq\Demo
 */
class DemoCallback extends BaseCallbackAbstract
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
