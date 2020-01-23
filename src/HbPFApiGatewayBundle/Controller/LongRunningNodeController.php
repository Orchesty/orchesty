<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LongRunningNodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class LongRunningNodeController extends AbstractController
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
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::processAction',
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
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::testAction',
            ['nodeId' => $nodeId]
        );
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
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksByIdAction',
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
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksAction',
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
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksByIdAction',
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
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksAction',
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
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::updateLongRunningAction',
            [
                'request' => $request,
                'id'      => $id,
            ]
        );
    }

}
