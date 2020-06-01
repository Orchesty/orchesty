<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\NotificationSender\Model\Notification\NotificationSettingsManager;

/**
 * Class NotificationSettingsHandler
 *
 * @package Hanaboso\NotificationSender\Handler
 */
final class NotificationSettingsHandler
{

    /**
     * @var NotificationSettingsManager
     */
    private NotificationSettingsManager $manager;

    /**
     * NotificationSettingsHandler constructor.
     *
     * @param NotificationSettingsManager $manager
     */
    public function __construct(NotificationSettingsManager $manager)
    {
        $this->manager = $manager;
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
     */
    public function saveSettings(string $id, array $data): array
    {
        return $this->manager->saveSettings($id, $data);
    }

}
