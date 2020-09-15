<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\Handler;

use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\PipesFramework\Notification\Model\NotificationManager;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class NotificationHandler
 *
 * @package Hanaboso\PipesFramework\HbPFNotificationBundle\Handler
 */
final class NotificationHandler
{

    /**
     * @var NotificationManager
     */
    private NotificationManager $manager;

    /**
     * NotificationHandler constructor.
     *
     * @param NotificationManager $manager
     */
    public function __construct(NotificationManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return mixed[]
     * @throws NotificationException
     * @throws CurlException
     * @throws JsonException
     */
    public function getSettings(): array
    {
        return Json::decode($this->manager->getSettings()->getBody());
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws CurlException
     * @throws NotificationException
     * @throws JsonException
     */
    public function getSetting(string $id): array
    {
        return Json::decode($this->manager->getSetting($id)->getBody());
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws CurlException
     * @throws NotificationException
     * @throws JsonException
     */
    public function updateSettings(string $id, array $data): array
    {
        return Json::decode($this->manager->updateSettings($id, $data)->getBody());
    }

}
