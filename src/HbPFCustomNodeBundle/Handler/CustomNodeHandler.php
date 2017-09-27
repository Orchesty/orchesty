<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:54 PM
 */

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
     * @param string $data
     * @param array  $headers
     *
     * @return ProcessDto
     */
    public function process(string $nodeId, $data, array $headers): ProcessDto
    {
        $dto = (new ProcessDto())
            ->setData($data)
            ->setHeaders($headers);

        $node = $this->loader->get($nodeId);

        return $node->process($dto);
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