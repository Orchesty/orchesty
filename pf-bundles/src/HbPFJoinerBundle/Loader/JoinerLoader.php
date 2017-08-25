<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:36 PM
 */

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Loader;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JoinerLoader
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Loader
 */
final class JoinerLoader
{

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
     * @return object
     * @throws JoinerException
     */
    public function get(string $joiner)
    {
        $name = sprintf('hbpf.joiner.%s', $joiner);
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }

        throw new JoinerException(
            sprintf('Joiner [%s] not found.', $joiner),
            JoinerException::JOINER_SERVICE_NOT_FOUND
        );
    }

}