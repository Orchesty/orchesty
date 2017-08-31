<?php
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
        echo "receive:" . print_r($data, 1);
        return new CallbackStatus(CallbackStatus::FAILED_DONE);
    }

}
