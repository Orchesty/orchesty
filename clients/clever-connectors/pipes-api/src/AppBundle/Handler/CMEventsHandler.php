<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 15:16
 */

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CMEventsManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CMEventHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class CMEventsHandler
{

    /**
     * @var CMEventsManager
     */
    private $manager;

    /**
     * CMEventHandler constructor.
     *
     * @param CMEventsManager $manager
     */
    public function __construct(CMEventsManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Request $request
     * @param string  $userId
     *
     * @throws CleverConnectorsException
     */
    public function createEvent(Request $request, string $userId): void
    {
        $this->manager->runEvent($request, $userId, SystemInstall::EVENT_CREATE);
    }

    /**
     * @param Request $request
     * @param string  $userId
     *
     * @throws CleverConnectorsException
     */
    public function unsubscribeEvent(Request $request, string $userId): void
    {
        $this->manager->runEvent($request, $userId, SystemInstall::EVENT_UNSUBSCRIBE);
    }

    /**
     * @param Request $request
     * @param string  $userId
     *
     * @throws CleverConnectorsException
     */
    public function hardBounceEvent(Request $request, string $userId): void
    {
        $this->manager->runEvent($request, $userId, SystemInstall::EVENT_HARD_BOUNCE);
    }

}