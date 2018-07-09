<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\DataLayout;
use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventsManager;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\DataLayout\LayoutManager;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\MapTemplate\MapManager;
use CleverConnectors\AppBundle\Model\SystemMetrics\SystemMetricsDto;
use CleverConnectors\AppBundle\Model\SystemMetrics\SystemMetricsInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Model\Systems\Dto\SystemData;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\WebhookManager;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\DateTimeUtils;
use CleverConnectors\AppBundle\Utils\InnerRequestUtils;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;

/**
 * Class SystemManager
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
class SystemManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SystemLoader
     */
    private $systemLoader;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemRepository;

    /**
     * @var WebhookManager
     */
    private $webhookManager;

    /**
     * @var RequestHandler
     */
    private $requestHandler;

    /**
     * @var CMEventsManager
     */
    private $eventsManager;

    /**
     * @var MapManager
     */
    private $mapManager;

    /**
     * @var LayoutManager
     */
    private $layoutManager;

    /**
     * @var SystemMetricsInterface
     */
    private $systemMetrics;

    /**
     * @var SystemLimitManager
     */
    private $systemLimitManager;

    /**
     * @var SystemTopologyRunner
     */
    private $systemTopologyRunner;

    /**
     * SystemManager constructor.
     *
     * @param DocumentManager        $dm
     * @param SystemLoader           $systemLoader
     * @param WebhookManager         $webhookManager
     * @param RequestHandler         $requestHandler
     * @param CMEventsManager        $eventsManager
     * @param MapManager             $mapManager
     * @param LayoutManager          $layoutManager
     * @param SystemMetricsInterface $systemMetrics
     * @param SystemLimitManager     $systemLimitManager
     * @param SystemTopologyRunner   $systemTopologyRunner
     */
    public function __construct(
        DocumentManager $dm,
        SystemLoader $systemLoader,
        WebhookManager $webhookManager,
        RequestHandler $requestHandler,
        CMEventsManager $eventsManager,
        MapManager $mapManager,
        LayoutManager $layoutManager,
        SystemMetricsInterface $systemMetrics,
        SystemLimitManager $systemLimitManager,
        SystemTopologyRunner $systemTopologyRunner
    )
    {
        $this->dm                   = $dm;
        $this->systemLoader         = $systemLoader;
        $this->systemRepository     = $dm->getRepository(SystemInstall::class);
        $this->webhookManager       = $webhookManager;
        $this->requestHandler       = $requestHandler;
        $this->eventsManager        = $eventsManager;
        $this->mapManager           = $mapManager;
        $this->layoutManager        = $layoutManager;
        $this->systemMetrics        = $systemMetrics;
        $this->systemLimitManager   = $systemLimitManager;
        $this->systemTopologyRunner = $systemTopologyRunner;
    }

    /**
     * @param string $key
     *
     * @return SystemInterface
     * @throws SystemException
     */
    public function getSystem(string $key): SystemInterface
    {
        return $this->systemLoader->getSystem($key);
    }

    /**
     * @param string|null $user
     * @param string|null $group
     *
     * @return SystemInterface[]
     * @throws SystemException
     */
    public function getSystems(?string $user = NULL, ?string $group = NULL): array
    {
        return $this->systemLoader->getSystems($user, $group);
    }

    /**
     * @param string $user
     *
     * @return SystemInterface[]
     * @throws SystemException
     */
    public function getUserSystems(string $user): array
    {
        $systems = [];

        /** @var SystemInstall $systemInstall */
        foreach ($this->systemRepository->findBy(['user' => $user]) as $systemInstall) {
            $systems[] = $this->systemLoader->getSystem($systemInstall->getSystem());
        }

        return $systems;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws SystemException
     */
    public function getUserSystem(SystemInstall $systemInstall): array
    {
        $system                 = $this->systemLoader->getSystem($systemInstall->getSystem());
        $data                   = $system->toArray($systemInstall);
        $data['setting_fields'] = $system->getSettingFields($systemInstall);

        if ($system->isDynamicMapper()) {
            $data['actions']       = $system->getAllowedActionsArray();
            $data['data_layouts']  = $this->getSystemInstallDataLayoutsArray($systemInstall->getId());
            $data['map_templates'] = $this->getSystemInstallMapTemplatesArray($systemInstall->getId());
        }

        return $data;
    }

    /**
     * @param string    $system
     * @param int       $page
     * @param int       $limit
     * @param bool|null $synchronized
     *
     * @return string[]
     * @throws SystemException
     */
    public function getSystemUsers(string $system, int $page, int $limit, ?bool $synchronized = NULL): array
    {
        $this->systemLoader->getSystem($system);
        $offset = --$page * $limit;

        /** @var SystemInstall[] $systems */
        if (is_null($synchronized)) {
            $systems = $this->systemRepository->findBy(['system' => $system], NULL, $limit, $offset);
        } else {
            $systems = $this->systemRepository->findBy(
                ['system' => $system, 'synchronized' => $synchronized],
                NULL,
                $limit,
                $offset
            );
        }
        $users = [];

        foreach ($systems as $systemInstall) {
            $users[] = $systemInstall->getUser();
        }

        return $users;
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return SystemInstall
     * @throws SystemException
     */
    public function getSystemInstall(string $user, string $system): SystemInstall
    {
        $systemInstall = $this->getSystemInstallOrNull($user, $system);

        if (!$systemInstall) {
            throw new SystemException(
                sprintf('System \'%s\' or user \'%s\' not found', $system, $user),
                SystemException::SYSTEM_OR_USER_NOT_FOUND
            );
        }

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return SystemInstall|null
     */
    public function getSystemInstallOrNull(string $user, string $system): ?SystemInstall
    {
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemRepository->findOneBy(['user' => $user, 'system' => $system]);

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $system
     * @param string $token
     *
     * @return SystemInstall
     * @throws SystemException
     */
    public function installSystem(string $user, string $system, string $token): SystemInstall
    {
        $this->systemLoader->getSystem($system);
        $systemInstall = (new SystemInstall())
            ->setUser($user)
            ->setSystem($system)
            ->setToken($token)
            ->setSynchronized(FALSE);

        $this->dm->persist($systemInstall);
        $this->dm->flush();

        $this->subscribeWebhooks($systemInstall);

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return bool
     * @throws SystemException
     */
    public function uninstallSystem(string $user, string $system): bool
    {
        $systemInstall = $this->getSystemInstall($user, $system);

        $this->unSubscribeWebhooks($systemInstall);
        $this->mapManager->removeBySystemInstall($systemInstall);
        $this->layoutManager->removeBySystemInstall($systemInstall);

        $this->dm->remove($systemInstall);
        $this->dm->flush();

        return TRUE;
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param array  $data
     *
     * @return SystemInstall
     * @throws SystemException
     * @throws MongoDBException
     */
    public function saveSystemSettings(string $user, string $systemKey, array $data): SystemInstall
    {
        $systemInstall = $this->getSystemInstall($user, $systemKey);

        /** @var AuthorizationInterface $system */
        $system = $this->getSystem($systemKey);
        $this->activateEvents($system, $systemInstall, $data);
        $system->setSettings($systemInstall, $data);

        $this->dm->flush();

        $this->subscribeWebhooks($systemInstall);

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $system
     * @param string $token
     *
     * @return SystemInstall
     * @throws SystemException
     * @throws MongoDBException
     */
    public function switchToken(string $user, string $system, string $token): SystemInstall
    {
        $systemInstall = $this->getSystemInstall($user, $system);
        $oldToken      = $systemInstall->getToken();
        $systemInstall->setToken($token);
        $this->dm->flush();

        $this->updateWebhooks($systemInstall);
        $this->runSwitchTokenTopologies($systemInstall, $oldToken);

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return int
     * @throws SystemException
     * @throws MongoDBException
     */
    public function synchronizeSubscriptions(string $user, string $system): int
    {
        $topologies = $this->runTopologies($user, $system, TopologyNameUtils::SYNC, []);

        return count($topologies);
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param string $password
     *
     * @return SystemInstall
     * @throws SystemException
     */
    public function setPassword(string $user, string $systemKey, string $password): SystemInstall
    {
        $systemInstall = $this->getSystemInstall($user, $systemKey);

        /** @var AuthorizationInterface $system */
        $system = $this->getSystem($systemKey);
        $system->setPassword($systemInstall, $password);

        $this->dm->flush();

        $this->subscribeWebhooks($systemInstall);

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param string $redirectUrl
     *
     * @throws SystemException
     */
    public function authorize(string $user, string $systemKey, string $redirectUrl): void
    {
        /** @var OAuth1Interface $system */
        $system        = $this->systemLoader->getSystem($systemKey);
        $systemInstall = $this->getSystemInstall($user, $systemKey);

        $system->saveFrontendRedirectUrl($systemInstall, $redirectUrl);
        $this->dm->flush();

        $system->authorize($systemInstall);
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param array  $data
     *
     * @return SystemInstall
     * @throws SystemException
     */
    public function saveToken(string $user, string $systemKey, array $data): SystemInstall
    {
        $systemInstall = $this->getSystemInstall($user, $systemKey);
        /** @var OAuth1Interface $system */
        $system = $this->systemLoader->getSystem($systemKey);
        $system->saveToken($systemInstall, $data);
        $system->setSettings($systemInstall, $data);
        $this->dm->flush();

        $this->subscribeWebhooks($systemInstall);

        return $systemInstall;
    }

    /**
     * @param Topology|null $topology
     * @param array         $webhooks
     * @param Node[]        $nodes
     * @param LastSync[]    $syncs
     *
     * @throws SystemException
     */
    public function deleteTopology(
        ?Topology $topology = NULL,
        array $webhooks = [],
        array $nodes = [],
        array $syncs = []
    ): void
    {
        if (!empty($webhooks)) {
            foreach ($webhooks as $webhook) {
                /** @var WebhookSystemInterface $system */
                $system = $this->systemLoader->getSystem($webhook['systemKey']);
                $this->webhookManager->unsubscribe($system, $webhook['user']);
            }
        }

        if ($topology) {
            $topology->setDeleted(TRUE);
            $this->requestHandler->deleteTopology($topology->getId());
        }

        foreach ($syncs as $sync) {
            $sync->setDeleted(TRUE);
        }
        foreach ($nodes as $node) {
            $node->setDeleted(TRUE);
        }

        $this->dm->flush();
    }

    /**
     * @param string $systemKey
     * @param string $user
     * @param string $action
     * @param array  $data
     *
     * @return array
     * @throws SystemException
     */
    public function runCustomAction(string $systemKey, string $user, string $action, array $data = []): array
    {
        $systemInstall = $this->getSystemInstall($user, $systemKey);
        /** @var SystemInterface $system */
        $system = $this->systemLoader->getSystem($systemKey);

        if (method_exists($system, $action)) {
            $output = $system->$action($systemInstall, $data);
            $this->dm->flush();

            return $output;
        }

        throw new SystemException(
            sprintf('Action "%s" does not exist for "%s" system.', $action, $systemKey),
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
    }

    /**
     * @return int
     * @throws SystemException
     */
    public function getSystemCount(): int
    {
        return count($this->getSystems());
    }

    /**
     * @param bool $toArray
     *
     * @return array
     * @throws EnumException
     * @throws SystemException
     */
    public function getSystemList(bool $toArray = FALSE): array
    {
        $systems = $this->getSystems();

        $res = [];
        /** @var SystemInterface $system */
        foreach ($systems as $system) {
            $data = new SystemData(
                $system->getKey(),
                $system->getName(),
                $system->getUIType(),
                $this->systemRepository->getUserCount($system->getKey()),
                $this->getSystemRequestCount(
                    [$system->getKey()],
                    DateTimeUtils::getUTCDateTime('-1 month')
                )[$system->getKey()]
            );

            $res[] = $toArray ? $data->toArray() : $data;
        }

        return $res;
    }

    /**
     * @param array         $systemKeys
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param null|string   $interval
     * @param null|string   $guid
     *
     * @return array
     * @throws EnumException
     */
    public function getSystemMetrics(
        array $systemKeys,
        ?DateTime $from = NULL,
        ?DateTime $to = NULL,
        ?string $interval = NULL,
        ?string $guid = NULL
    ): array
    {
        $dto = new SystemMetricsDto($systemKeys, $from, $to, $interval, $guid);

        return $this->systemMetrics->getSystemMetrics($dto);
    }

    /**
     * @param array         $systemKeys
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param null|string   $interval
     * @param null|string   $guid
     *
     * @return array
     * @throws EnumException
     */
    public function getSystemRequestCount(
        array $systemKeys,
        ?DateTime $from = NULL,
        ?DateTime $to = NULL,
        ?string $interval = NULL,
        ?string $guid = NULL
    ): array
    {
        $dto = new SystemMetricsDto($systemKeys, $from, $to, $interval, $guid);

        return $this->systemMetrics->getSystemRequestCount($dto);
    }

    /**
     * ------------------------------------- HELPERS ----------------------------------------
     */

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     * @param array           $data
     *
     * @throws SystemException
     * @throws MongoDBException
     */
    protected function activateEvents(SystemInterface $system, SystemInstall $systemInstall, array &$data): void
    {
        if ($system instanceof CMEventSystemInterface) {
            $this->eventsManager->saveEventsForSystemInstall($systemInstall, $data);
        }
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @throws SystemException
     */
    protected function subscribeWebhooks(SystemInstall $systemInstall): void
    {
        $systemService = $this->systemLoader->getSystem($systemInstall->getSystem());

        if ($systemService->isAuthorized($systemInstall) && SystemTypeEnum::isWebhook($systemService->getType())) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->subscribe($webhookSystem, $systemInstall->getUser(), $systemInstall->getToken());
        }
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @throws SystemException
     */
    protected function updateWebhooks(SystemInstall $systemInstall): void
    {
        $systemService = $this->systemLoader->getSystem($systemInstall->getSystem());

        if ($systemService->isAuthorized($systemInstall) && SystemTypeEnum::isWebhook($systemService->getType())) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->update($webhookSystem, $systemInstall->getUser(), $systemInstall->getToken());
        }
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @throws SystemException
     */
    protected function unSubscribeWebhooks(SystemInstall $systemInstall): void
    {
        $systemService = $this->systemLoader->getSystem($systemInstall->getSystem());

        if ($systemService->isAuthorized($systemInstall) && SystemTypeEnum::isWebhook($systemService->getType())) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->unsubscribe($webhookSystem, $systemInstall->getUser());
        }
    }

    /**
     * @param string $user
     * @param string $system
     * @param string $topology
     * @param array  $data
     *
     * @return array
     * @throws SystemException
     * @throws MongoDBException
     */
    private function runTopologies(string $user, string $system, string $topology, array $data): array
    {
        $systemInstall = $this->getSystemInstall($user, $system);
        $system        = $this->systemLoader->getSystem($system);
        $request       = InnerRequestUtils::getRequest($systemInstall, $data);
        $request->setMethod(CurlManager::METHOD_POST);

        $this->systemLimitManager->addSystemLimitToRequestHeaders($request->headers, $system, $systemInstall);

        return $this->systemTopologyRunner->runTopologies($topology, $systemInstall, $system, $request);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $token
     *
     * @throws SystemException
     * @throws MongoDBException
     */
    private function runSwitchTokenTopologies(SystemInstall $systemInstall, string $token): void
    {
        $this->runTopologies(
            $systemInstall->getUser(),
            $systemInstall->getSystem(),
            TopologyNameUtils::SWITCH_TOKEN,
            ['token' => $token]
        );
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private function getSystemInstallDataLayoutsArray(string $id): array
    {
        $dataLayouts = $this->dm->getRepository(DataLayout::class)->findBy([
            'systemInstall' => $id,
        ]);

        $dataLayoutArray = [];
        foreach ($dataLayouts as $dataLayout) {
            $dataLayoutArray[] = $dataLayout->toArray();
        }

        return $dataLayoutArray;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private function getSystemInstallMapTemplatesArray(string $id): array
    {
        $mapTemplates = $this->dm->getRepository(MapTemplate::class)->findBy([
            'systemInstall' => $id,
        ]);

        $mapTemplatesArray = [];
        foreach ($mapTemplates as $mapTemplate) {
            $mapTemplatesArray[] = $mapTemplate->toArray();
        }

        return $mapTemplatesArray;
    }

}