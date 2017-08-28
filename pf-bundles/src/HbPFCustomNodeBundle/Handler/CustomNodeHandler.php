<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:54 PM
 */

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler;

use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Loader\CustomNodeLoader;

/**
 * Class CustomNodeHandler
 *
 * @package Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler
 */
class CustomNodeHandler
{

    /**
     * @var CustomNodeLoader
     */
    private $loader;

    /**
     * CustomNodeHandler constructor.
     *
     * @param CustomNodeLoader $loader
     */
    function __construct(CustomNodeLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param string $nodeId
     * @param array  $data
     *
     * @return array
     */
    public function process(string $nodeId, array $data): array
    {
        $node = $this->loader->get($nodeId);
        $res  = $node->process($data);

        return $res;
    }

    /**
     * @param string $joinerId
     *
     * @throws CustomNodeException
     */
    public function processTest(string $joinerId): void
    {
        $this->loader->get($joinerId);
    }

}