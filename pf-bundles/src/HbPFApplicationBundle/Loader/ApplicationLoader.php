<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Loader;

use Exception;
use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ApplicationLoader
 *
 * @package Hanaboso\PipesFramework\HbPFApplicationBundle\Loader
 */
class ApplicationLoader
{

    private const APPLICATION_PREFIX = 'hbpf.application';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ApplicationLoader constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $key
     *
     * @return ApplicationInterface
     * @throws
     */
    public function getApplication(string $key): ApplicationInterface
    {
        $name = sprintf('%s.%s', self::APPLICATION_PREFIX, $key);

        if ($this->container->has($name)) {
            /** @var ApplicationInterface $application */
            $application = $this->container->get($name);
        } else {
            throw new Exception(
                sprintf('Application for [%s] was not found.', $key)
            );
        }

        return $application;
    }

    /**
     * @return array
     */
    public function getApplications(): array
    {
        $list = Yaml::parse((string) file_get_contents(__DIR__ . '/../Resources/config/applications.yml'));
        $res  = [];

        foreach (array_keys($list['services']) as $key) {
            $shortened = str_replace(sprintf('%s.', self::APPLICATION_PREFIX), '', (string) $key);

            $res[] = $shortened;
        }

        return $res;
    }

}