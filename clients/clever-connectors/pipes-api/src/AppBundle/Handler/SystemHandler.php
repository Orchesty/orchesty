<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Model\Systems\SystemManager;
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
     * SystemHandler constructor.
     *
     * @param SystemManager $manager
     */
    public function __construct(SystemManager $manager)
    {
        $this->manager = $manager;
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

}