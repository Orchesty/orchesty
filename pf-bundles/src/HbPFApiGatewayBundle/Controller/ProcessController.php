<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ProcessController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class ProcessController extends AbstractController
{

    use ControllerTrait;

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/processes', methods: [Request::METHOD_GET])]
    public function getProcessesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\ProcessController::getProcessesAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/processes/total', methods: [Request::METHOD_GET])]
    public function getProcessesTotalAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\ProcessController::getProcessesTotalAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/processes/graph', methods: [Request::METHOD_GET])]
    public function getProcessesGraphAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\ProcessController::getProcessesGraphAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/processes/topologies', methods: [Request::METHOD_GET])]
    public function getProcessesTopologiesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\ProcessController::getProcessesTopologies',
            ['request' => $request],
        );
    }

}
