<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\CurlHandlerAbstract;
use Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract;
use Hanaboso\NotificationSender\Model\Notification\Handler\RabbitHandlerAbstract;
use Hanaboso\NotificationSender\Model\Notification\Sender\CurlSender;
use Hanaboso\NotificationSender\Model\Notification\Sender\EmailSender;
use Hanaboso\NotificationSender\Model\Notification\Sender\RabbitSender;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Throwable;

/**
 * Class NotificationManager
 *
 * @package Hanaboso\NotificationSender\Model\Notification
 */
final class NotificationManager implements LoggerAwareInterface
{

    private const PIPE = 'pipes';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var RewindableGenerator|CurlHandlerAbstract[]|EmailHandlerAbstract[]|RabbitHandlerAbstract[]
     */
    private $handlers;

    /**
     * @var CurlSender
     */
    private $curlSender;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var RabbitSender
     */
    private $rabbitSender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * NotificationManager constructor.
     *
     * @param DocumentManager            $dm
     * @param RewindableGenerator<mixed> $handlers
     * @param CurlSender                 $curlSender
     * @param EmailSender                $emailSender
     * @param RabbitSender               $rabbitSender
     */
    public function __construct(
        DocumentManager $dm,
        RewindableGenerator $handlers,
        CurlSender $curlSender,
        EmailSender $emailSender,
        RabbitSender $rabbitSender
    )
    {
        $this->dm           = $dm;
        $this->handlers     = $handlers;
        $this->curlSender   = $curlSender;
        $this->emailSender  = $emailSender;
        $this->rabbitSender = $rabbitSender;
        $this->logger       = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string  $event
     * @param mixed[] $data
     */
    public function send(string $event, array $data): void
    {
        $data = array_merge($data, $data[self::PIPE] ?? []);
        unset($data[self::PIPE]);

        foreach ($this->handlers as $handler) {
            $class = get_class($handler);

            /** @var NotificationSettings|null $settings */
            $settings = $this->dm->getRepository(NotificationSettings::class)->findOneBy(
                [
                    NotificationSettings::EVENTS     => $event,
                    NotificationSettings::CLASS_NAME => $class,
                ]
            );

            if ($settings) {
                $this->logger->debug(
                    sprintf(
                        'sending notification from sender manager: [settings=%s] [parentClass=%s]',
                        Json::encode($settings->toArray($event, $class)),
                        get_parent_class($handler)
                    )
                );
            } else {
                $this->logger->debug(
                    sprintf(
                        'No settings found: [parentClass=%s]',
                        get_parent_class($handler)
                    )
                );
            }

            if ($settings) {
                try {
                    /** @var string $parentClass */
                    $parentClass = get_parent_class($handler);

                    switch ($parentClass) {
                        case CurlHandlerAbstract::class:
                            /** @var CurlDto $dto */
                            $dto = $handler->process($data);

                            $this->curlSender->send($dto, $settings->getSettings());

                            break;
                        case EmailHandlerAbstract::class:
                            /** @var EmailDto $dto */
                            $dto = $handler->process($data);

                            $this->emailSender->send($dto, $settings->getSettings());

                            break;
                        case RabbitHandlerAbstract::class:
                            /** @var RabbitDto $dto */
                            $dto = $handler->process($data);

                            $this->rabbitSender->send($dto, $settings->getSettings());

                            break;
                        default:
                            throw new NotificationException(
                                sprintf("Notification sender for notification handler '%s' not found!", $class),
                                NotificationException::NOTIFICATION_SENDER_NOT_FOUND
                            );
                    }
                } catch (Throwable $t) {
                    $this->logger->error($t->getMessage(), $t->getTrace());
                }
            }
        }
    }

}
