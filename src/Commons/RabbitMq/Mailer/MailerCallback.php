<?php
/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 14.9.17
 * Time: 10:32
 */

namespace Hanaboso\PipesFramework\Commons\RabbitMq\Mailer;

use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\BaseCallbackAbstract;
use Hanaboso\PipesFramework\Commons\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\HbPFMailerBundle\DefaultValues\DefaultValues;
use Hanaboso\PipesFramework\Mailer\Mailer;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\MessageBuilderException;
use Hanaboso\PipesFramework\Mailer\Transport\TransportException;
use Swift_TransportException;

/**
 * Class MailerCallback
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq\Mailer
 */
class MailerCallback extends BaseCallbackAbstract
{

    /**
     * @var Mailer
     */
    protected $mailer;
    /**
     * @var GenericMessageBuilder
     */
    protected $handler;

    /**
     * @var DefaultValues
     */
    protected $defaultValues;

    /**
     * MailerCallback constructor.
     *
     * @param Mailer                $mailer
     * @param GenericMessageBuilder $handler
     * @param DefaultValues         $defaultValues
     */
    public function __construct(Mailer $mailer, GenericMessageBuilder $handler, DefaultValues $defaultValues)
    {
        parent::__construct();

        $this->mailer        = $mailer;
        $this->handler       = $handler;
        $this->defaultValues = $defaultValues;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    function handle($data, Message $message): CallbackStatus
    {
        $data = DefaultValues::handleDefaults(
            $data,
            $this->defaultValues->getDefaults('user_manager'),
            ['from', 'subject']
        );

        try {
            $this->mailer->renderAndSend(
                $this->handler->buildTransportMessage($data)
            );

            return new CallbackStatus(CallbackStatus::RESEND);
        } catch (TransportException | Swift_TransportException $e) {

        return new CallbackStatus(CallbackStatus::RESEND, $e->getMessage());
    } catch (MessageBuilderException $e) {

        return new CallbackStatus(CallbackStatus::FAILED, $e->getMessage());
    }
    }

}
