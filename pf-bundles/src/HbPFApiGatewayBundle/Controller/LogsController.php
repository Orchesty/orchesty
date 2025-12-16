<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class LogsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class LogsController extends AbstractController
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/logs-old', methods: ['GET'])]
    public function topologyMetricsAction(Request $request): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFLogsBundle\Controller\LogsController::getDataForTableAction',
            [],
            $request->query->all(),
        );
    }
    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/logs', methods: [Request::METHOD_GET])]
    public function getLogsAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFLogsBundle\Controller\LogsController::getLogsAction',
            [],
            $request->query->all(),
        );
    }

}
