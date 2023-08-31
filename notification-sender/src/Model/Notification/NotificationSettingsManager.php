<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
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
use Hanaboso\NotificationSender\Repository\NotificationSettingsRepository;
use Hanaboso\Utils\Exception\EnumException;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Throwable;

/**
 * Class NotificationSettingsManager
 *
 * @package Hanaboso\NotificationSender\Model\Notification
 */
final class NotificationSettingsManager
{

    private const TEST_MESSAGE = [EmailHandlerAbstract::SUBJECT => 'Pipes Framework: Notification settings test message'];

    /**
     * @var ObjectRepository<NotificationSettings>&NotificationSettingsRepository
     */
    private NotificationSettingsRepository $repository;

    /**
     * NotificationSettingsManager constructor.
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
        $this->repository = $dm->getRepository(NotificationSettings::class);
    }

    /**
     * @return mixed[]
     * @throws MongoDBException
     */
    public function listSettings(): array
    {
        /** @var NotificationSettings[] $settings */
        $settings = [];
        $data     = [];

        /** @var NotificationSettings $setting */
        foreach ($this->repository->findAll() as $setting) {
            $settings[$setting->getClass()] = $setting;
        }

        foreach ($this->handlers as $handler) {
            $class   = $handler::class;
            $setting = $settings[$class] ?? NULL;

            if (!$setting) {
                $setting = (new NotificationSettings())->setClass($class);
                $this->dm->persist($setting);
            }

            $data[] = $setting->toArray($handler->getType(), $handler->getName());
            unset($settings[$class]);
        }

        foreach ($settings as $setting) {
            $this->dm->remove($setting);
        }

        $this->dm->flush();

        return $data;
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws NotificationException
     */
    public function getSettings(string $id): array
    {
        $setting = $this->getById($id);
        $handler = $this->getHandlerByClass($setting->getClass());

        return $setting->toArray($handler->getType(), $handler->getName());
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws NotificationException
     * @throws MongoDBException
     * @throws EnumException
     */
    public function saveSettings(string $id, array $data): array
    {
        $setting = $this->getById($id);
        $handler = $this->getHandlerByClass($setting->getClass());

        if (isset($data[NotificationSettings::EVENTS])) {
            $setting->setEvents($data[NotificationSettings::EVENTS]);
        }

        if (isset($data[NotificationSettings::SETTINGS])) {
            foreach ($handler->getRequiredSettings() as $required) {
                if (!isset($data[NotificationSettings::SETTINGS][$required])) {
                    throw new NotificationException(
                        sprintf("Required settings '%s' for type '%s' is missing!", $required, $handler->getType()),
                        NotificationException::NOTIFICATION_PARAMETER_NOT_FOUND,
                    );
                }
            }

            foreach (array_keys($data[NotificationSettings::SETTINGS]) as $key) {
                if (!in_array($key, $handler->getRequiredSettings(), TRUE)) {
                    unset($data[NotificationSettings::SETTINGS][$key]);
                }
            }

            $setting->setSettings($data[NotificationSettings::SETTINGS]);
        }

        $this->validateSettings($setting, $handler, $data[NotificationSettings::SETTINGS]);

        $this->dm->flush();
        $this->dm->refresh($setting);

        return $setting->toArray($handler->getType(), $handler->getName());
    }

    /**
     * @param string $id
     *
     * @return NotificationSettings
     * @throws NotificationException
     */
    private function getById(string $id): NotificationSettings
    {
        /** @var NotificationSettings|null $settings */
        $settings = $this->repository->findOneBy([NotificationSettings::ID => $id]);

        if (!$settings) {
            throw new NotificationException(
                sprintf("NotificationSettings with key '%s' not found!", $id),
                NotificationException::NOTIFICATION_SETTINGS_NOT_FOUND,
            );
        }

        return $settings;
    }

    /**
     * @param string $class
     *
     * @return CurlHandlerAbstract|EmailHandlerAbstract|RabbitHandlerAbstract
     * @throws NotificationException
     */
    private function getHandlerByClass(string $class): CurlHandlerAbstract|EmailHandlerAbstract|RabbitHandlerAbstract
    {
        foreach ($this->handlers as $handler) {
            if ($handler::class === $class) {
                return $handler;
            }
        }

        throw new NotificationException(
            sprintf("Notification handler '%s' not found!", $class),
            NotificationException::NOTIFICATION_HANDLER_NOT_FOUND,
        );
    }

    /**
     * @param NotificationSettings                                           $setting
     * @param CurlHandlerAbstract|EmailHandlerAbstract|RabbitHandlerAbstract $handler
     * @param mixed[]                                                        $settings
     *
     * @throws NotificationException
     */
    private function validateSettings(NotificationSettings $setting, $handler, array $settings): void
    {
        try {
            switch ($handler->getType()) {
                case NotificationSenderEnum::CURL:
                    /** @var CurlDto $dto */
                    $dto = $handler->process(self::TEST_MESSAGE);

                    $this->curlSender->send($dto, $settings);

                    break;
                case NotificationSenderEnum::EMAIL:
                    /** @var EmailDto $dto */
                    $dto = $handler->process(self::TEST_MESSAGE);

                    $this->emailSender->sendEmail($dto, $settings);

                    break;
                case NotificationSenderEnum::RABBIT:
                    /** @var RabbitDto $dto */
                    $dto = $handler->process(self::TEST_MESSAGE);

                    $this->rabbitSender->send($dto, $settings);

                    break;
                default:
                    throw new NotificationException(
                        sprintf("Notification sender for notification handler '%s' not found!", $handler::class),
                        NotificationException::NOTIFICATION_SENDER_NOT_FOUND,
                    );
            }

            $setting->setStatus(TRUE)->setStatusMessage(NULL);
        } catch (NotificationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $setting->setStatus(FALSE)->setStatusMessage($e->getMessage());
        }
    }

}