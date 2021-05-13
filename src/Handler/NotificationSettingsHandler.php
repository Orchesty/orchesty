<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager;
use Hanaboso\Utils\Exception\EnumException;

/**
 * Class NotificationSettingsHandler
 *
 * @package Hanaboso\NotificationSender\Handler
 */
final class NotificationSettingsHandler
{

    /**
     * NotificationSettingsHandler constructor.
     *
     * @param NotificationSettingsManager $manager
     */
    public function __construct(private NotificationSettingsManager $manager)
    {
    }

    /**
     * @return mixed[]
     * @throws MongoDBException
     */
    public function listSettings(): array
    {
        return ['items' => $this->manager->listSettings()];
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws NotificationException
     */
    public function getSettings(string $id): array
    {
        return $this->manager->getSettings($id);
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
        return $this->manager->saveSettings($id, $data);
    }

}
