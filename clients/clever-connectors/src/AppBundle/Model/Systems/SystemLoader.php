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
     * @var array
     */
    protected $systemsWithTagSystem;

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
     * @param array $systemsWithTagSystem
     *
     * @return SystemLoader
     */
    public function setSystemsWithTagSystem(array $systemsWithTagSystem): SystemLoader
    {
        $this->systemsWithTagSystem = array_keys($systemsWithTagSystem);

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
     * @param string $tag
     *
     * @return SystemInterface[]
     * @throws SystemException
     */
    public function getSystems(string $tag): array
    {
        $systems  = [];
        $property = sprintf('systemsWithTag%s', Strings::firstUpper($tag));

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