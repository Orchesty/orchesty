<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Mailer;

use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Handler\MailHandler;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Hanaboso\PipesFramework\User\Model\Messages\UserMessageAbstract;

/**
 * Class Mailer
 *
 * @package Hanaboso\PipesFramework\User\Model\Mailer
 */
class Mailer
{

    private const DEFAULT_MAIL_BUILDER = 'generic';

    /**
     * @var MailHandler
     */
    private $mailHandler;

    /**
     * @var AbstractProducer
     */
    private $producer;

    /**
     * @var string
     */
    private $from;

    /**
     * @var bool
     */
    private $async;

    /**
     * @var string
     */
    private $builderId;

    /**
     * Mailer constructor.
     *
     * @param AbstractProducer $producer
     * @param MailHandler      $mailHandler
     * @param string           $from
     * @param bool             $async
     * @param string           $builderId
     *
     * @throws MailerException
     */
    public function __construct(
        AbstractProducer $producer,
        MailHandler $mailHandler,
        string $from,
        bool $async = TRUE,
        ?string $builderId = NULL
    )
    {
        $this->mailHandler = $mailHandler;
        $this->producer    = $producer;
        $this->from        = $from;
        $this->async       = $async;
        $this->builderId   = $builderId;

        if ($this->async === FALSE && empty($this->builderId)) {
            $this->builderId = self::DEFAULT_MAIL_BUILDER;
        }
    }

    /**
     * @param UserMessageAbstract $message
     */
    public function send(UserMessageAbstract $message): void
    {
        if ($this->async) {
            $this->producer->publish(json_encode($message->getMessage()));
        } else {
            $data         = $message->getMessage();
            $data['from'] = $this->from;

            $this->mailHandler->send($this->builderId, $data);
        }
    }

}