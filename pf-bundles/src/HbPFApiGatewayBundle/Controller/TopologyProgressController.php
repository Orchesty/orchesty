<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TopologyProgressController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class TopologyProgressController extends AbstractController
{

    use ControllerTrait;

    /**
     * @Route("/progress/topology/{topologyId}", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topologyId
     *
     * @return Response
     */
    public function getProgressTopologyAction(Request $request, string $topologyId): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyProgressController::getProgressTopologyAction',
            [
                'request'    => $request,
                'topologyId' => $topologyId,
            ],
        );
    }

    /**
     * @Route("/progress", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getProgressesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyProgressController::getProgressesAction',
            [
                'request'    => $request,
            ],
        );
    }

}
