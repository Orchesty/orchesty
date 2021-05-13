<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification;

use Doctrine\ODM\MongoDB\DocumentManager;
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
use Hanaboso\Utils\String\Json;
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
        private DocumentManager $dm,
        private RewindableGenerator $handlers,
        private CurlSender $curlSender,
        private EmailSender $emailSender,
        private RabbitSender $rabbitSender,
    )
    {
        $this->logger = new NullLogger();
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
        $this->dm->clear();

        foreach ($this->handlers as $handler) {
            $class = $handler::class;
            /** @var string $parentClass */
            $parentClass = get_parent_class($handler);

            /** @var NotificationSettings|null $settings */
            $settings = $this->dm->getRepository(NotificationSettings::class)->findOneBy(
                [
                    NotificationSettings::EVENTS     => $event,
                    NotificationSettings::CLASS_NAME => $class,
                ],
            );

            if ($settings) {
                $this->logger->debug(
                    sprintf(
                        'sending notification from sender manager: [settings=%s] [parentClass=%s]',
                        Json::encode($settings->toArray($event, $class)),
                        $parentClass,
                    ),
                );
            } else {
                $this->logger->debug(sprintf('No settings found: [parentClass=%s]', $parentClass));
            }

            if ($settings) {
                try {
                    switch ($parentClass) {
                        case CurlHandlerAbstract::class:
                            /** @var CurlDto $dto */
                            $dto = $handler->process($data);

                            $this->curlSender->send($dto, $settings->getSettings());

                            break;
                        case EmailHandlerAbstract::class:
                            /** @var EmailDto $dto */
                            $dto = $handler->process($data);

                            $this->emailSender->sendEmail($dto, $settings->getSettings());

                            break;
                        case RabbitHandlerAbstract::class:
                            /** @var RabbitDto $dto */
                            $dto = $handler->process($data);

                            $this->rabbitSender->send($dto, $settings->getSettings());

                            break;
                        default:
                            throw new NotificationException(
                                sprintf("Notification sender for notification handler '%s' not found!", $class),
                                NotificationException::NOTIFICATION_SENDER_NOT_FOUND,
                            );
                    }
                } catch (Throwable $t) {
                    $this->logger->error($t->getMessage(), $t->getTrace());
                }
            }
        }
    }

}
