<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 11:28
 */

namespace Hanaboso\PipesFramework\Commons\Repeater;

use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\BaseCallbackAbstract;
use Hanaboso\PipesFramework\Commons\RabbitMq\CallbackStatus;

/**
 * Class RepeaterCallback
 *
 * @package Hanaboso\PipesFramework\Commons\Repeater
 */
class RepeaterCallback extends BaseCallbackAbstract
{

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    function handle($data, Message $message): CallbackStatus
    {
        return new CallbackStatus(CallbackStatus::SUCCESS_DONE);
    }

}
