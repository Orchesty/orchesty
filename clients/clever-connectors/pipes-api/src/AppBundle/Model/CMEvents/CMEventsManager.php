<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 10:47
 */

namespace CleverConnectors\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemTopologyRunner;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\InnerRequestUtils;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CMEventsManager
 *
 * @package CleverConnectors\AppBundle\Model\CMEvents
 */
class CMEventsManager implements LoggerAwareInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    private $systemRepo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     * @var SystemLimitManager
     */
    private $systemLimitManager;

    /**
     * @var SystemTopologyRunner
     */
    private $systemTopologyRunner;

    /**
     * CMEventHandler constructor.
     *
     * @param DocumentManager      $dm
     * @param SystemLoader         $loader
     * @param SystemLimitManager   $systemLimitManager
     * @param SystemTopologyRunner $systemTopologyRunner
     */
    public function __construct(
        DocumentManager $dm,
        SystemLoader $loader,
        SystemLimitManager $systemLimitManager,
        SystemTopologyRunner $systemTopologyRunner
    )
    {
        $this->dm                   = $dm;
        $this->systemRepo           = $this->dm->getRepository(SystemInstall::class);
        $this->loader               = $loader;
        $this->systemLimitManager   = $systemLimitManager;
        $this->systemTopologyRunner = $systemTopologyRunner;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param string  $userId
     * @param string  $event
     *
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function runEvent(Request $request, string $userId, string $event): void
    {
        SystemInstall::checkEvent($event);
        $request->headers->set(CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE), $event);
        switch ($event) {
            case SystemInstall::EVENT_CREATE:
                $const = TopologyNameUtils::CREATE_CONTACT;
                break;
            case SystemInstall::EVENT_UNSUBSCRIBE:
                $const = TopologyNameUtils::UNSUBSCRIBE_CONTACT;
                break;
            case SystemInstall::EVENT_HARD_BOUNCE:
                $const = TopologyNameUtils::HARD_BOUNCE_CONTACT;
                break;
            case SystemInstall::EVENT_SUBSCRIBE:
                $const = TopologyNameUtils::SUBSCRIBE_CONTACT;
                break;
            default:
                $const = TopologyNameUtils::UPDATE_CONTACT;
                break;
        }

        /** @var SystemInstall $systemInstall */
        foreach ($this->systemRepo->getSystemInstallByEvent($event, $userId) as $systemInstall) {
            InnerRequestUtils::addCMHeaders($systemInstall, $request);
            $system = $this->loader->getSystem($systemInstall->getSystem());
            $this->systemLimitManager->addSystemLimitToRequestHeaders($request->headers, $system, $systemInstall);
            try {
                $this->systemTopologyRunner->runTopologies($const, $systemInstall, $system, $request);
            } catch (Exception $e) {
                $this->logger->alert($e->getMessage(), ['exception' => $e]);
            }
        }
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function saveEventsForSystemInstall(
        SystemInstall $systemInstall,
        array &$data
    ): SystemInstall
    {
        /** @var SystemInterface|CMEventSystemInterface $system */
        $system  = $this->loader->getSystem($systemInstall->getSystem());
        $changed = $this->getChanges($system, $systemInstall, $data);

        if (empty($changed)) {
            return $systemInstall;
        }

        $request = InnerRequestUtils::getRequest($systemInstall, $changed);
        $request->setMethod(CurlManager::METHOD_POST);

        $this->systemLimitManager->addSystemLimitToRequestHeaders($request->headers, $system, $systemInstall);

        try {
            $this->systemTopologyRunner->runTopologies(TopologyNameUtils::ACTIVATE_EVENT, $systemInstall, $system,
                $request);
        } catch (Exception $e) {
            $this->logger->alert($e->getMessage(), ['exception' => $e]);
        }

        return $systemInstall;
    }

    /**
     * -------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param CMEventSystemInterface $system
     * @param SystemInstall          $systemInstall
     * @param array                  $data
     *
     * @return array
     */
    private function getChanges(CMEventSystemInterface $system, SystemInstall $systemInstall, array &$data): array
    {
        $changed = [];

        if (array_key_exists(SystemInstall::EVENT_CREATE, $data)) {
            if ($system->isEventAllowed(SystemInstall::EVENT_CREATE) === TRUE) {
                $systemInstall->setEventCreate($data[SystemInstall::EVENT_CREATE]);
            }

            unset($data[SystemInstall::EVENT_CREATE]);
        }

        $this->processEventData($system, $systemInstall, $data, $changed, SystemInstall::EVENT_UNSUBSCRIBE);
        $this->processEventData($system, $systemInstall, $data, $changed, SystemInstall::EVENT_HARD_BOUNCE);
        $this->processEventData($system, $systemInstall, $data, $changed, SystemInstall::EVENT_SUBSCRIBE);

        return $changed;
    }

    /**
     * @param CMEventSystemInterface $system
     * @param SystemInstall          $systemInstall
     * @param array                  $data
     * @param array                  $changed
     * @param string                 $event
     */
    private function processEventData(
        CMEventSystemInterface $system,
        SystemInstall $systemInstall,
        array &$data,
        array &$changed,
        string $event
    ): void
    {
        if (array_key_exists($event, $data)) {
            if ($system->isEventAllowed($event) === TRUE) {
                if ($systemInstall->getEventState($event) === FALSE && $data[$event] === TRUE && $system->isEventProcessAllowed($event)) {
                    $changed[] = $event;
                }

                $systemInstall->setEventState($event, $data[$event]);
            }
            unset($data[$event]);
        }
    }

}