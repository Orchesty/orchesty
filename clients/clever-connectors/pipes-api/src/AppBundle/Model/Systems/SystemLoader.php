<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Nette\Utils\Strings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SystemLoader
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
class SystemLoader
{

    private const PREFIX = 'systems';

    /**
     * @var string[]
     */
    protected $systemsWithTagSystems;

    /**
     * @var string[]
     */
    protected $systemsWithTagSystemsDev;

    /**
     * @var string[]
     */
    protected $systemsWithTagSystemsUserSomeUser;

    /**
     * @var string[]
     */
    protected $systemsWithTagSystemsGroupSomeGroup;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * SystemLoader constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string[] $systemsWithTagSystems
     *
     * @return SystemLoader
     */
    public function setSystemsWithTagSystems(array $systemsWithTagSystems): SystemLoader
    {
        $this->systemsWithTagSystems = $systemsWithTagSystems;

        return $this;
    }

    /**
     * @param string[] $systemsWithTagSystemsDev
     *
     * @return SystemLoader
     */
    public function setSystemsWithTagSystemsDev(array $systemsWithTagSystemsDev): SystemLoader
    {
        $this->systemsWithTagSystemsDev = $systemsWithTagSystemsDev;

        return $this;
    }

    /**
     * @param string[] $systemsWithTagSystemsUserSomeUser
     *
     * @return SystemLoader
     */
    public function setSystemsWithTagSystemsUserSomeUser(array $systemsWithTagSystemsUserSomeUser): SystemLoader
    {
        $this->systemsWithTagSystemsUserSomeUser = $systemsWithTagSystemsUserSomeUser;

        return $this;
    }

    /**
     * @param string[] $systemsWithTagSystemsGroupSomeGroup
     *
     * @return SystemLoader
     */
    public function setSystemsWithTagSystemsGroupSomeGroup(array $systemsWithTagSystemsGroupSomeGroup): SystemLoader
    {
        $this->systemsWithTagSystemsGroupSomeGroup = $systemsWithTagSystemsGroupSomeGroup;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return SystemInterface
     * @throws SystemException
     */
    public function getSystem(string $key): SystemInterface
    {
        $key = sprintf('%s.%s', self::PREFIX, $key);

        if ($this->container->has($key)) {
            $system = $this->container->get($key);
            if ($system instanceof SystemInterface) {
                return $system;
            }
        }

        throw new SystemException(
            sprintf('System \'%s\' not found', $key),
            SystemException::SYSTEM_NOT_FOUND
        );
    }

    /**
     * @param null|string $user
     * @param null|string $group
     *
     * @return array
     * @throws SystemException
     */
    public function getSystems(?string $user = NULL, ?string $group = NULL): array
    {
        if ($user && $group) {
            return $this->getSystemsByUserAndGroup($user, $group);
        } else if ($user) {
            return $this->getSystemsByUser($user);
        } else if ($group) {
            return $this->getSystemsByGroup($group);
        } else {
            return $this->getSystemsBySystem();
        }
    }

    /**
     * @param string $user
     * @param string $group
     *
     * @return array
     */
    private function getSystemsByUserAndGroup(string $user, string $group): array
    {
        $systems      = [];
        $groupSystems = $this->getSystemsByGroup($group);
        $userSystems  = $this->getSystemsByUser($user);
        foreach ($groupSystems as $system) {
            if (in_array($system, $userSystems, TRUE)) {
                $systems[] = $system;
            }
        }

        return $systems;
    }

    /**
     * @param string $user
     *
     * @return SystemInterface[]
     * @throws SystemException
     */
    private function getSystemsByUser(string $user): array
    {
        $systems  = [];
        $property = sprintf('systemsWithTagSystemsUser%s', Strings::firstUpper($user));
        if (property_exists(__CLASS__, $property)) {
            if ($this->$property) {
                foreach ($this->$property as $system) {
                    $systems[] = $this->container->get($system);
                }
            }

            return $systems;
        }

        throw new SystemException(
            sprintf('System property \'%s\' not found', $property),
            SystemException::SYSTEM_PROPERTY_NOT_FOUND
        );
    }

    /**
     * @param string $group
     *
     * @return SystemInterface[]
     * @throws SystemException
     */
    private function getSystemsByGroup(string $group): array
    {
        $systems  = [];
        $property = sprintf('systemsWithTagSystemsGroup%s', Strings::firstUpper($group));
        if (property_exists(__CLASS__, $property)) {
            if ($this->$property) {
                foreach ($this->$property as $system) {
                    $systems[] = $this->container->get($system);
                }
            }

            return $systems;
        }

        throw new SystemException(
            sprintf('System property \'%s\' not found', $property),
            SystemException::SYSTEM_PROPERTY_NOT_FOUND
        );
    }

    /**
     * @return SystemInterface[]
     * @throws SystemException
     */
    private function getSystemsBySystem(): array
    {
        $systems  = [];
        $property = 'systemsWithTagSystems';
        if (property_exists(__CLASS__, $property)) {
            if ($this->$property) {
                foreach ($this->$property as $system) {
                    $systems[] = $this->container->get($system);
                }
            }

            return $systems;
        }

        throw new SystemException(
            sprintf('System property \'%s\' not found', $property),
            SystemException::SYSTEM_PROPERTY_NOT_FOUND
        );
    }

}