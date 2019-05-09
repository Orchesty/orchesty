<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Loader;

use Exception;
use Hanaboso\PipesFramework\Application\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesFramework\Utils\NodeServiceLoaderUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * @return BasicApplicationInterface
     * @throws Exception
     */
    public function getApplication(string $key): BasicApplicationInterface
    {
        $name = sprintf('%s.%s', self::APPLICATION_PREFIX, $key);

        if ($this->container->has($name)) {
            /** @var BasicApplicationInterface $application */
            $application = $this->container->get($name);
        } else {
            throw new Exception(
                sprintf('Application for [%s] was not found.', $key)
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