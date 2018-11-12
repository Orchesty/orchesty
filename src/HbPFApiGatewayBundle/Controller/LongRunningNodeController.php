<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LongRunningNodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class LongRunningNodeController extends FOSRestController
{

    /**
     * @Route("/longRunning/run/topology/{topoName}/node/{nodeName}", methods={"POST", "GET", "OPTIONS"})
     * @Route("/longRunning/run/topology/{topoName}/node/{nodeName}/token/{token}", methods={"POST", "GET", "OPTIONS"})
     *
     * @param Request     $request
     * @param string      $topoName
     * @param string      $nodeName
     * @param null|string $token
     *
     * @return Response
     */
    public function runTokenAction(Request $request, string $topoName, string $nodeName, ?string $token = NULL): Response
    {
        $data = ['request' => $request, 'topoName' => $topoName, 'nodeName' => $nodeName];
        if ($token) {
            $data['token'] = $token;
        }

        return $this->forward('HbPFLongRunningNodeBundle:LongRunningNode:run', $data);
    }

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
        return $this->forward('HbPFLongRunningNodeBundle:LongRunningNode:process',
            ['request' => $request, 'nodeId' => $nodeId]);
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
     * @Route("/longRunning/topology/{topo}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topo
     *
     * @return Response
     */
    public function getTasksAction(string $topo): Response
    {
        return $this->forward('HbPFLongRunningNodeBundle:LongRunningNode:getTasks',
            ['topo' => $topo]);
    }

    /**
     * @Route("/longRunning/topology/{topo}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topo
     * @param string $node
     *
     * @return Response
     */
    public function getNodeTasksAction(string $topo, string $node): Response
    {
        return $this->forward('HbPFLongRunningNodeBundle:LongRunningNode:getNodeTasks',
            ['topo' => $topo, 'node' => $node]);
    }

}