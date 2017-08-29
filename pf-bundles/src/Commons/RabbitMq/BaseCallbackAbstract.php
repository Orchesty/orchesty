<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 11:26
 */

namespace Hanaboso\PipesFramework\Commons\RabbitMq;

use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\Exception\RabbitMqException;
use Hanaboso\PipesFramework\Commons\RabbitMq\Repeater\Repeater;

/**
 * Class BaseCallbackAbstract
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq
 */
abstract class BaseCallbackAbstract
{

    /**
     * @var Repeater | NULL
     */
    protected $repeater = NULL;

    final public function handleMessage($data, Message $message)
    {
        $result = $this->handle($data, $message);

        switch ($result->getStatus()) {
            case CallbackStatus::SUCCESS_DONE:
                //TODO: what else
                break;
            case CallbackStatus::FAILED_DONE:
                //TODO: log
                if ($this->getRepeater()) {
                    $this->getRepeater()->add($message);
                }
                break;
            default:
                //TODO: log
                throw new RabbitMqException(
                    sprintf('Unknown callback status code: ', $result->getStatus()),
                    RabbitMqException::UNKNOWN_CALLBACK_STATUS_CODE
                );
        }
    }

    /**
     * @return Repeater|NULL
     */
    public function getRepeater(): ?Repeater
    {
        return $this->repeater;
    }

    /**
     * @param Repeater $repeater
     *
     * @return BaseCallbackAbstract
     */
    public function setRepeater(Repeater $repeater)
    {
        $this->repeater = $repeater;

        return $this;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    abstract function handle($data, Message $message): CallbackStatus;

}
