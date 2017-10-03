<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\WebhookManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

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
     * SystemManager constructor.
     *
     * @param DocumentManager $dm
     * @param SystemLoader    $systemLoader
     * @param WebhookManager  $webhookManager
     */
    public function __construct(DocumentManager $dm, SystemLoader $systemLoader, WebhookManager $webhookManager)
    {
        $this->dm               = $dm;
        $this->systemLoader     = $systemLoader;
        $this->systemRepository = $dm->getRepository(SystemInstall::class);
        $this->webhookManager   = $webhookManager;
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
     * @param string $user
     * @param string $system
     * @param string $token
     *
     * @return SystemInstall
     */
    public function installSystem(string $user, string $system, string $token): SystemInstall
    {
        $systemService = $this->systemLoader->getSystem($system);

        $systemInstall = (new SystemInstall())
            ->setUser($user)
            ->setSystem($system)
            ->setToken($token)
            ->setSynchronized(FALSE);

        if ($systemService->getType() === SystemTypeEnum::WEBHOOK) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->subscribe($webhookSystem, $user, $token);
        }

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

        $systemService = $this->systemLoader->getSystem($system);
        if ($systemService->getType() === SystemTypeEnum::WEBHOOK) {
            /** @var WebhookSystemInterface $webhookSystem */
            $webhookSystem = $systemService;
            $this->webhookManager->unsubscribe($webhookSystem, $user);
        }

        $this->dm->remove($systemInstall);
        $this->dm->flush();

        return TRUE;
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

        return $systemInstall;
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
     * @param string $password
     *
     * @return SystemInstall
     */
    public function setPassword(string $user, string $system, string $password): SystemInstall
    {
        $systemInstall = $this->getSystemInstall($user, $system);

        $settings             = $systemInstall->getSettings();
        $settings['password'] = $password;

        $systemInstall->setSettings($settings);
        $this->dm->flush();

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return SystemInstall
     * @throws SystemException
     */
    private function getSystemInstall(string $user, string $system): SystemInstall
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

}