<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Loader\CustomNodeLoader;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CustomNodeHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler
 */
final class CustomNodeHandler
{

    /**
     * CustomNodeHandler constructor.
     *
     * @param CustomNodeLoader $loader
     */
    function __construct(private CustomNodeLoader $loader)
    {
    }

    /**
     * @param string  $nodeId
     * @param Request $request
     *
     * @return ProcessDto
     * @throws CustomNodeException
     */
    public function processAction(string $nodeId, Request $request): ProcessDto
    {
        $dto  = ProcessDtoFactory::createFromRequest($request);
        $node = $this->loader->get($nodeId);

        return $node->processAction($dto);
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

    /**
     * @return mixed[]
     */
    public function getCustomNodes(): array
    {
        return array_map(static fn($customNode) => $customNode->toArray(), $this->loader->getList());
    }

}
