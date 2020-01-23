<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class LogsController extends AbstractController
{

    /**
     * @Route("/logs", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function topologyMetricsAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFLogsBundle\Controller\LogsController::getDataForTableAction',
            [],
            $request->query->all()
        );
    }

}
