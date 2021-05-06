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
     * @Route("/longRunning/{id}/process", methods={"POST", "GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processAction(Request $request, string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::processAction',
            ['request' => $request, 'id' => $id],
        );
    }

    /**
     * @Route("/longRunning/{id}/process/test", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function testAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::testAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/longRunning/id/topology/{topology}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topology
     *
     * @return Response
     */
    public function getTasksByAction(string $topology): Response
    {
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksByIdAction',
            ['topology' => $topology],
        );
    }

    /**
     * @Route("/longRunning/name/topology/{topology}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topology
     *
     * @return Response
     */
    public function getTasksAction(string $topology): Response
    {
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksAction',
            ['topology' => $topology],
        );
    }

    /**
     * @Route("/longRunning/id/topology/{topology}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topology
     * @param string $node
     *
     * @return Response
     */
    public function getNodeTasksByIdAction(string $topology, string $node): Response
    {
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksByIdAction',
            ['topology' => $topology, 'node' => $node],
        );
    }

    /**
     * @Route("/longRunning/name/topology/{topology}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param string $topology
     * @param string $node
     *
     * @return Response
     */
    public function getNodeTasksAction(string $topology, string $node): Response
    {
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksAction',
            ['topology' => $topology, 'node' => $node],
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
            ],
        );
    }

}
