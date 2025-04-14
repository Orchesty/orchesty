<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Loader;

use Hanaboso\CommonsBundle\Utils\NodeServiceLoader;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApplicationLoader
 *
 * @package Hanaboso\PipesPhpSdk\Application\Loader
 */
final class ApplicationLoader
{

    private const string APPLICATION_PREFIX = 'hbpf.application';

    /**
     * ApplicationLoader constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param string $key
     *
     * @return ApplicationInterface
     * @throws ApplicationInstallException
     */
    public function getApplication(string $key): ApplicationInterface
    {
        $name = sprintf('%s.%s', self::APPLICATION_PREFIX, $key);

        if ($this->container->has($name)) {
            /** @var ApplicationInterface $application */
            $application = $this->container->get($name);
        } else {
            throw new ApplicationInstallException(
                sprintf('Application for [%s] was not found.', $key),
                ApplicationInstallException::APP_WAS_NOT_FOUND,
            );
        }

        return $application;
    }

    /**
     * @param mixed[] $exclude
     *
     * @return ApplicationInterface[]
     */
    public function getApplications($exclude = []): array
    {
        $dirs = $this->container->getParameter('applications');

        return NodeServiceLoader::getServices($dirs, self::APPLICATION_PREFIX, $exclude);
    }

}
