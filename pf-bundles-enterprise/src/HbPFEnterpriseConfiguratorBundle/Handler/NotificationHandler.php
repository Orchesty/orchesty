<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Hanaboso\PipesFrameworkEnterprise\Configurator\Notification\NotificationException;
use Hanaboso\PipesFrameworkEnterprise\Configurator\Notification\NotificationManager;
use Hanaboso\Utils\String\Json;

/**
 * Class NotificationHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class NotificationHandler
{

    /**
     * NotificationHandler constructor.
     *
     * @param NotificationManager $manager
     */
    public function __construct(private NotificationManager $manager)
    {
    }

    /**
     * @param string $userId
     *
     * @return mixed[]
     * @throws NotificationException
     */
    public function listSubscriptions(string $userId): array
    {
        $response = $this->manager->listSubscriptions($userId);

        return Json::decode($response->getBody());
    }

    /**
     * @param string $userId
     * @param string $body
     *
     * @return mixed[]
     * @throws NotificationException
     */
    public function upsertSubscription(string $userId, string $body): array
    {
        $response = $this->manager->upsertSubscription($userId, $body);

        return Json::decode($response->getBody());
    }

}
