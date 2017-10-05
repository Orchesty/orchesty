<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
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
     * @var SystemLoader
     */
    private $loader;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * SystemHandler constructor.
     *
     * @param SystemManager   $manager
     * @param SystemLoader    $loader
     * @param DocumentManager $dm
     */
    public function __construct(SystemManager $manager, SystemLoader $loader, DocumentManager $dm)
    {
        $this->manager = $manager;
        $this->loader  = $loader;
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

        foreach ($this->manager->getSystems($user, $group) as $innerSystem) {
            $systems[] = $innerSystem->toArray();
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
     * @param string $system
     * @param array  $data
     *
     * @return array
     */
    public function installSystem(string $user, string $system, array $data): array
    {
        ControllerUtils::checkParameters(['token'], $data);
        $this->manager->installSystem($user, $system, $data['token']);

        return [];
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
     * @internal param string $token
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
        /** @var OAuth1Interface $system */
        $system        = $this->loader->getSystem($systemKey);
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
     * @return string
     */
    public function saveToken(string $user, string $systemKey, array $data): string
    {
        $systemInstall = $this->getSystemInstall($user, $systemKey);
        $system = $this->loader->getSystem($systemKey);
        $system->setSettings($systemInstall, $data);
        $this->dm->flush();

        return $systemInstall->getSettings()[OAuth1Interface::FRONTEND_REDIRECT_URL];
    }

    /**
     * @param string $user
     * @param string $systemKey
     *
     * @return SystemInstall
     * @throws CleverConnectorsException
     */
    private function getSystemInstall(string $user, string $systemKey): SystemInstall
    {
        $systemInstall = $this->dm->getRepository(SystemInstall::class)->findOneBy([
            'user'   => $user,
            'system' => $systemKey,
        ]);

        if (!$systemInstall) {
            throw new CleverConnectorsException(
                'For given system and user installed system was not found.',
                CleverConnectorsException::SYSTEM_NOT_INSTALLED
            );
        }

        return $systemInstall;
    }

}