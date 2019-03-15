<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Loader;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesFramework\Joiner\JoinerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JoinerLoader
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Loader
 */
final class JoinerLoader
{

    public const PREFIX = 'hbpf.joiner';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * JoinerLoader constructor.
     *
     * @param ContainerInterface $container
     */
    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            JoinerException::JOINER_SERVICE_NOT_FOUND
        );
    }

}
