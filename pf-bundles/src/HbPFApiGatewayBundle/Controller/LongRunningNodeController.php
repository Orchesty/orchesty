<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LongRunningNodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class LongRunningNodeController extends AbstractFOSRestController
{

    /**
     * @Route("/longRunning/{nodeId}/process", methods={"POST", "GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $nodeId
     *
     * @return Response
     */
    public function processAction(Request $request, string $nodeId): Response
    {
        return $this->forward(
            'HbPFLongRunningNodeBundle:LongRunningNode:process',
            ['request' => $request, 'nodeId' => $nodeId]
        );
    }

    /**
     * @Route("/longRunning/{nodeId}/process/test", methods={"GET", "OPTIONS"})
     *
     * @param string $nodeId
     *
     * @return Response
     */
    public function testAction(string $nodeId): Response
    {
        return $this->forward('HbPFLongRunningNodeBundle:LongRunningNode:test', ['nodeId' => $nodeId]);
    }

    /**
     * @Route("/longRunning/id/topology/{topo}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topo
     *
     * @return Response
     */
    public function getTasksByAction(string $topo): Response
    {
        return $this->forward(
            'HbPFLongRunningNodeBundle:LongRunningNode:getTasksById',
            ['topo' => $topo]
        );
    }

    /**
     * @Route("/longRunning/name/topology/{topo}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topo
     *
     * @return Response
     */
    public function getTasksAction(string $topo): Response
    {
        return $this->forward(
            'HbPFLongRunningNodeBundle:LongRunningNode:getTasks',
            ['topo' => $topo]
        );
    }

    /**
     * @Route("/longRunning/id/topology/{topo}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topo
     * @param string $node
     *
     * @return Response
     */
    public function getNodeTasksByIdAction(string $topo, string $node): Response
    {
        return $this->forward(
            'HbPFLongRunningNodeBundle:LongRunningNode:getNodeTasksById',
            ['topo' => $topo, 'node' => $node]
        );
    }

    /**
     * @Route("/longRunning/name/topology/{topo}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topo
     * @param string $node
     *
     * @return Response
     */
    public function getNodeTasksAction(string $topo, string $node): Response
    {
        return $this->forward(
            'HbPFLongRunningNodeBundle:LongRunningNode:getNodeTasks',
            ['topo' => $topo, 'node' => $node]
        );
    }

    /**
     * @Route("/longRunning/{id}", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateLongRunningAction(Request $request, string $id): Response
    {
        return $this->forward(
            'HbPFLongRunningNodeBundle:LongRunningNode:updateLongRunning',
            [
                'request' => $request,
                'id'      => $id,
            ]
        );
    }

}
