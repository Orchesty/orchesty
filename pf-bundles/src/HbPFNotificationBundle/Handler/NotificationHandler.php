<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\Handler;

use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\PipesFramework\Notification\Model\NotificationManager;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Class NotificationHandler
 *
 * @package Hanaboso\PipesFramework\HbPFNotificationBundle\Handler
 */
class NotificationHandler
{

    /**
     * @var NotificationManager
     */
    private $manager;

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
     * @return array
     * @throws NotificationException
     * @throws JsonException
     * @throws CurlException
     */
    public function getSettings(): array
    {
        return Json::decode($this->manager->getSettings()->getBody(), TRUE);
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws JsonException
     * @throws NotificationException
     * @throws CurlException
     */
    public function updateSettings(array $data): array
    {
        return Json::decode($this->manager->updateSettings($data)->getBody(), TRUE);
    }

}