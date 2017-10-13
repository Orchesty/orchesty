<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\WebhookManager;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Symfony\Component\HttpFoundation\Request;

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
     * @var SystemInstallRepository|DocumentRepository
     */
    private $systemRepository;

    /**
     * @var WebhookManager
     */
    private $webhookManager;

    /**
     * @var StartingPoint
     */
    private $startingPoint;

    /**
     * SystemManager constructor.
     *
     * @param DocumentManager $dm
     * @param SystemLoader    $systemLoader
     * @param WebhookManager  $webhookManager
     * @param StartingPoint   $startingPoint
     */
    public function __construct(
        DocumentManager $dm,
        SystemLoader $systemLoader,
        WebhookManager $webhookManager,
        StartingPoint $startingPoint
    )
    {
        $this->dm               = $dm;
        $this->systemLoader     = $systemLoader;
        $this->systemRepository = $dm->getRepository(SystemInstall::class);
        $this->webhookManager   = $webhookManager;
        $this->startingPoint    = $startingPoint;
    }

    /**
     * @param string $key
     *
     * @return SystemInterface
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
     */
    public function getSystems(?string $user = NULL, ?string $group = NULL): array
    {
        return $this->systemLoader->getSystems($user, $group);
    }

    /**
     * @param string $user
     *
     * @return SystemInterface[]
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
     */
    public function getUserSystem(SystemInstall $systemInstall): array
    {
        $system                 = $this->systemLoader->getSystem($systemInstall->getSystem());
        $data                   = $system->toArray($systemInstall);
        $data['setting_fields'] = $system->getSettingFields($systemInstall);

        return $data;
    }

    /**
     * @param string $system
     * @param bool   $synchronized
     *
     * @return string[]
     */
    public function getSystemUsers(string $system, bool $synchronized): array
    {
        $this->systemLoader->getSystem($system);

        $users   = [];
        $systems = $this->systemRepository->findBy(['system' => $system, 'synchronized' => $synchronized]);

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
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemRepository->findOneBy(['user' => $user, 'system' => $system]);

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
     * @param string $token
     *
     * @return SystemInstall
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
     */
    public function saveSystemSettings(string $user, string $systemKey, array $data): SystemInstall
    {
        $systemInstall = $this->getSystemInstall($user, $systemKey);

        /** @var AuthorizationInterface $system */
        $system = $this->getSystem($systemKey);
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
     */
    public function switchToken(string $user, string $system, string $token): SystemInstall
    {
        $systemInstall = $this->getSystemInstall($user, $system);

        $systemInstall->setToken($token);
        $this->dm->flush();

        $this->updateWebhooks($systemInstall);

        return $systemInstall;
    }

    /**
     * @param $user
     * @param $system
     *
     * @throws CleverConnectorsException
     */
    public function synchronizeSubscriptions($user, $system): void
    {
        $request = new Request();
        $request->headers->set(CMHeaders::createKey(CMHeaders::GUID), $user);
        $request->headers->set(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), $system);

        $topologyName = sprintf('%s-sync-subscribers', $system);
        $topologies   = $this->dm->getRepository(Topology::class)->getRunnableTopologies($topologyName);

        foreach ($topologies as $topology) {
            $node = $this->dm->getRepository(Node::class)->findOneBy([
                'topology' => $topology->getId(),
                'type'     => TypeEnum::SIGNAL,
                'handler'  => HandlerEnum::EVENT,
            ]);

            if (!$node) {
                throw new CleverConnectorsException(
                    sprintf('Starting Node not found for topology [%s]', $topology->getId()),
                    CleverConnectorsException::STARTING_NODE_NOT_FOUND
                );
            }

            $this->startingPoint->runWithRequest($request, $topology, $node);
        }
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param string $password
     *
     * @return SystemInstall
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
            //TODO zastavit běžící topologii
            $topology->setDeleted(TRUE);
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
     * ------------------------------------- HELPERS ----------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     */
    protected function subscribeWebhooks(SystemInstall $systemInstall): void
    {
        $systemService = $this->systemLoader->getSystem($systemInstall->getSystem());

        if ($systemService->isAuthorized($systemInstall) && $systemService->getType() === SystemTypeEnum::WEBHOOK) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->subscribe($webhookSystem, $systemInstall->getUser(), $systemInstall->getToken());
        }
    }

    /**
     * @param SystemInstall $systemInstall
     */
    protected function updateWebhooks(SystemInstall $systemInstall): void
    {
        $systemService = $this->systemLoader->getSystem($systemInstall->getSystem());

        if ($systemService->isAuthorized($systemInstall) && $systemService->getType() === SystemTypeEnum::WEBHOOK) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->update($webhookSystem, $systemInstall->getUser(), $systemInstall->getToken());
        }
    }

    /**
     * @param SystemInstall $systemInstall
     */
    protected function unSubscribeWebhooks(SystemInstall $systemInstall): void
    {
        $systemService = $this->systemLoader->getSystem($systemInstall->getSystem());

        if ($systemService->isAuthorized($systemInstall) && $systemService->getType() === SystemTypeEnum::WEBHOOK) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->unsubscribe($webhookSystem, $systemInstall->getUser());
        }
    }

}