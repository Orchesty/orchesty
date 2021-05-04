<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyProgressHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TopologyProgressController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class TopologyProgressController
{

    use ControllerTrait;

    /**
     * TopologyProgressController constructor.
     *
     * @param TopologyProgressHandler $handler
     */
    public function __construct(private TopologyProgressHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @Route("/progress/topology/{topologyId}", methods={"GET", "OPTIONS"})
     *
     * @param string $topologyId
     *
     * @return Response
     */
    public function getProgressTopologyAction(string $topologyId): Response
    {
        $data = $this->handler->getProgress($topologyId);

        return $this->getResponse($data);
    }

}
