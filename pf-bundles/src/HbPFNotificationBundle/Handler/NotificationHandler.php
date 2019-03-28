<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\Handler;

use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\PipesFramework\Notification\Model\NotificationManager;

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
     * @throws CurlException
     */
    public function getSettings(): array
    {
        return json_decode($this->manager->getSettings()->getBody(), TRUE);
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws NotificationException
     * @throws CurlException
     */
    public function updateSettings(array $data): array
    {
        return json_decode($this->manager->updateSettings($data)->getBody(), TRUE);
    }

}
