<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:54 PM
 */

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Handler;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Loader\JoinerLoader;

/**
 * Class JoinerHandler
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Handler
 */
class JoinerHandler
{

    /**
     * @var JoinerLoader
     */
    private $loader;

    /**
     * JoinerHandler constructor.
     *
     * @param JoinerLoader $loader
     */
    function __construct(JoinerLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param string $joinerId
     * @param array  $data
     *
     * @return array
     */
    public function processJoiner(string $joinerId, array $data): array
    {
        $joiner = $this->loader->get($joinerId);
        // TODO pip-82 call joinerInterface

        return [];
    }

    /**
     * @param string $joinerId
     * @param array  $data
     */
    public function processJoinerTest(string $joinerId, array $data): void
    {
        // TODO nullJoiner pip-82
        $joiner = $this->loader->get($joinerId);
    }

}