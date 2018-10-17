<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:54 PM
 */

namespace Hanaboso\PipesFramework\HbPFJoinerBundle\Handler;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
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
     * @throws JoinerException
     */
    public function processJoiner(string $joinerId, array $data): array
    {
        $joiner = $this->loader->get($joinerId);

        return $joiner->process($data['data'], $data['count']);
    }

    /**
     * @param string $joinerId
     * @param array  $data
     *
     * @throws JoinerException
     */
    public function processJoinerTest(string $joinerId, array $data): void
    {
        $this->loader->get($joinerId);

        if (!isset($data['data'])) {
            throw new JoinerException(
                'Data under \'data\' key are missing in request.',
                JoinerException::MISSING_DATA_IN_REQUEST
            );
        }
        if (!isset($data['count'])) {
            throw new JoinerException(
                'Total data count under \'count\' key is missing in request.',
                JoinerException::MISSING_DATA_IN_REQUEST
            );
        }
    }

}