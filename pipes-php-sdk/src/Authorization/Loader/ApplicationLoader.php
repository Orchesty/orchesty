<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Loader;

use Hanaboso\CommonsBundle\Utils\NodeServiceLoaderUtil;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApplicationLoader
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Loader
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
                ApplicationInstallException::APP_WAS_NOT_FOUND
            );
        }

        return $application;
    }

    /**
     * @param array $exclude
     *
     * @return array
     */
    public function getApplications($exclude = []): array
    {
        $dirs = $this->container->getParameter('applications');

        return NodeServiceLoaderUtil::getServices($dirs, self::APPLICATION_PREFIX, $exclude);
    }

}