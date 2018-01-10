<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\Handler;

use Hanaboso\PipesFramework\Notification\Model\NotificationManager;
use Nette\Utils\Json;

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
     */
    public function getSettings(): array
    {
        return Json::decode($this->manager->getSettings()->getBody(), TRUE);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function updateSettings(array $data): array
    {
        return Json::decode($this->manager->updateSettings($data)->getBody(), TRUE);
    }

}