<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader;

use Hanaboso\CommonsBundle\Utils\NodeServiceLoader;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesPhpSdk\Joiner\JoinerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JoinerLoader
 *
 * @package Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader
 */
final class JoinerLoader
{

    public const PREFIX = 'hbpf.joiner';

    /**
     * JoinerLoader constructor.
     *
     * @param ContainerInterface $container
     */
    function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param string $joiner
     *
     * @return JoinerInterface
     * @throws JoinerException
     */
    public function get(string $joiner): JoinerInterface
    {
        $name = sprintf('%s.%s', self::PREFIX, $joiner);
        if ($this->container->has($name)) {
            /** @var JoinerInterface $joiner */
            $joiner = $this->container->get($name);

            return $joiner;
        }

        throw new JoinerException(
            sprintf('Joiner [%s] not found.', $joiner),
            JoinerException::JOINER_SERVICE_NOT_FOUND,
        );
    }

    /**
     * @param mixed[] $exclude
     *
     * @return mixed[]
     */
    public function getAllJoiners(array $exclude = []): array
    {
        $dirs = $this->container->getParameter('node_services_dirs');

        return NodeServiceLoader::getServices($dirs, self::PREFIX, $exclude);
    }

}
