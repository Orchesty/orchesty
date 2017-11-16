<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Utils\ControllerUtils;

/**
 * Class SystemHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class SystemHandler
{

    /**
     * @var SystemManager
     */
    private $manager;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * SystemHandler constructor.
     *
     * @param SystemManager   $manager
     * @param DocumentManager $dm
     */
    public function __construct(SystemManager $manager, DocumentManager $dm)
    {
        $this->manager = $manager;
        $this->dm      = $dm;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getSystem(string $key): array
    {
        return $this->manager->getSystem($key)->toArray();
    }

    /**
     * @param null|string $user
     * @param null|string $group
     *
     * @return array
     */
    public function getSystems(?string $user = NULL, ?string $group = NULL): array
    {
        $systems = [];

        foreach ($this->manager->getSystems($user, $group) as $system) {
            $systems[] = $system->toArray();
        }

        return $systems;
    }

    /**
     * @param string $user
     *
     * @return array
     */
    public function getUserSystems(string $user): array
    {
        $systems = [];
        foreach ($this->manager->getUserSystems($user) as $system) {
            $systems[] = $system->toArray($this->manager->getSystemInstall($user, $system->getKey()));
        }

        return $systems;
    }

    /**
     * @param string $user
     * @param string $systemKey
     *
     * @return array
     */
    public function getUserSystem(string $user, string $systemKey): array
    {
        $systemInstall = $this->manager->getSystemInstall($user, $systemKey);

        return $this->manager->getUserSystem($systemInstall);
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function installSystem(string $user, string $system, array $data): array
    {
        ControllerUtils::checkParameters(['token'], $data);

        if ($this->isSystemInstalled($user, $system)) {
            throw new CleverConnectorsException(
                'Requested system has already been installed for current user.',
                CleverConnectorsException::SYSTEM_ALREADY_INSTALLED
            );
        }

        $systemInstall = $this->manager->installSystem($user, $system, $data['token']);

        return $this->manager->getUserSystem($systemInstall);
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     */
    public function saveSystemSettings(string $user, string $system, array $data): array
    {
        $this->manager->saveSystemSettings($user, $system, $data);

        return [];
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return array
     */
    public function uninstallSystem(string $user, string $system): array
    {
        $this->manager->uninstallSystem($user, $system);

        return [];
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     *
     */
    public function switchToken(string $user, string $system, array $data): array
    {
        ControllerUtils::checkParameters(['token'], $data);
        $this->manager->switchToken($user, $system, $data['token']);

        return [];
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return array
     */
    public function synchronizeSubscriptions(string $user, string $system): array
    {
        $runningTopologies = $this->manager->synchronizeSubscriptions($user, $system);

        return ['running_topologies' => $runningTopologies];
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     */
    public function setPassword(string $user, string $system, array $data): array
    {
        ControllerUtils::checkParameters(['password'], $data);
        $this->manager->setPassword($user, $system, $data['password']);

        return [];
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param string $redirectUrl
     */
    public function authorize(string $user, string $systemKey, string $redirectUrl): void
    {
        $this->manager->authorize($user, $systemKey, $redirectUrl);
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param array  $data
     *
     * @return string
     */
    public function saveToken(string $user, string $systemKey, array $data): string
    {
        $systemInstall = $this->manager->saveToken($user, $systemKey, $data);

        return $systemInstall->getSettings()[OAuth1Interface::FRONTEND_REDIRECT_URL];
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return bool
     * @throws CleverConnectorsException
     */
    public function isSystemInstalled(string $user, string $system): bool
    {
        $systemInstall = $this->dm->getRepository(SystemInstall::class)->findOneBy([
            'user'   => $user,
            'system' => $system,
        ]);

        return $systemInstall ? TRUE : FALSE;
    }

    /**
     * @param string $systemKey
     * @param string $user
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    public function runCustomAction(string $systemKey, string $user, string $action, array $data = []): array
    {
        return $this->manager->runCustomAction($systemKey, $user, $action, $data);
    }

}