<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\NotificationSender\Document\NotificationSettings;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\Handler\CurlHandlerAbstract;
use Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract;
use Hanaboso\NotificationSender\Model\Notification\Handler\RabbitHandlerAbstract;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

/**
 * Class NotificationSettingsManager
 *
 * @package Hanaboso\NotificationSender\Model\Notification
 */
final class NotificationSettingsManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var RewindableGenerator|CurlHandlerAbstract[]|EmailHandlerAbstract[]|RabbitHandlerAbstract[]
     */
    private $handlers;

    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     * NotificationSettingsManager constructor.
     *
     * @param DocumentManager     $dm
     * @param RewindableGenerator $handlers
     */
    public function __construct(DocumentManager $dm, RewindableGenerator $handlers)
    {
        $this->dm         = $dm;
        $this->handlers   = $handlers;
        $this->repository = $dm->getRepository(NotificationSettings::class);
    }

    /**
     * @return array
     * @throws DateTimeException
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
            $class   = get_class($handler);
            $setting = $settings[$class] ?? NULL;

            if (!$setting) {
                $setting = (new NotificationSettings())->setClass($class);
                $this->dm->persist($setting);
                $this->dm->flush();
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
     * @return array
     * @throws DocumentNotFoundException
     * @throws NotificationException
     */
    public function getSettings(string $id): array
    {
        $setting = $this->getById($id);
        $handler = $this->getHandlerByClass($setting->getClass());

        return $setting->toArray($handler->getType(), $handler->getName());
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     * @throws NotificationException
     * @throws DocumentNotFoundException
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
                        NotificationException::NOTIFICATION_PARAMETER_NOT_FOUND
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

        $this->dm->flush();

        return $setting->toArray($handler->getType(), $handler->getName());
    }

    /**
     * @param string $id
     *
     * @return NotificationSettings
     * @throws DocumentNotFoundException
     */
    private function getById(string $id): NotificationSettings
    {
        /** @var NotificationSettings|NULL $settings */
        $settings = $this->repository->findOneBy([NotificationSettings::ID => $id]);

        if (!$settings) {
            throw new DocumentNotFoundException(sprintf("NotificationSettings with key '%s' not found!", $id));
        }

        return $settings;
    }

    /**
     * @param string $class
     *
     * @return CurlHandlerAbstract|EmailHandlerAbstract|RabbitHandlerAbstract
     * @throws NotificationException
     */
    private function getHandlerByClass(string $class)
    {
        foreach ($this->handlers as $handler) {
            if (get_class($handler) === $class) {
                return $handler;
            }
        }

        throw new NotificationException(
            sprintf("Notification handler '%s' not found!", $class),
            NotificationException::NOTIFICATION_HANDLER_NOT_FOUND
        );
    }

}
