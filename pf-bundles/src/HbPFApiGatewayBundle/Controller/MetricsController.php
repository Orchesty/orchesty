<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class MetricsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class MetricsController extends AbstractController
{

    /**
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    #[Route('/metrics/topology/{topology}', methods: ['GET'])]
    public function topologyMetricsAction(Request $request, string $topology): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyMetricsAction',
            ['request' => $request, 'topology' => $topology],
        );
    }

    /**
     * @param Request $request
     * @param string  $topology
     * @param string  $node
     *
     * @return Response
     */
    #[Route('/metrics/topology/{topology}/node/{node}', methods: ['GET'])]
    public function nodeMetricsAction(Request $request, string $topology, string $node): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::nodeMetricsAction',
            ['request' => $request, 'topology' => $topology, 'node' => $node],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/healthcheck', methods: ['GET'])]
    public function healthcheckMetricsAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::healthcheckMetricsAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    #[Route('/metrics/topology/{topology}/requests', methods: ['GET'])]
    public function topologyRequestsCountMetricsAction(Request $request, string $topology): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyRequestsCountMetricsAction',
            ['request' => $request, 'topology' => $topology],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/connectors/overview', methods: [Request::METHOD_GET])]
    public function getMetricsConnectorsOverviewAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsConnectorsOverviewAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/connectors', methods: [Request::METHOD_GET])]
    public function getMetricsConnectorsAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsConnectorsAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/connectors/graph', methods: [Request::METHOD_GET])]
    public function getMetricsConnectorsGraphAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsConnectorsGraphAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/requests', methods: [Request::METHOD_GET])]
    public function getMetricsRequestsAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsRequestsAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/processes', methods: [Request::METHOD_GET])]
    public function getMetricsProcessesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsProcessesAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/limits', methods: [Request::METHOD_GET])]
    public function getLimitsAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsLimitsAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/limits/total', methods: [Request::METHOD_GET])]
    public function getLimitsTotalAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsLimitsTotalAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/metrics/limits/graph', methods: [Request::METHOD_GET])]
    public function getLimitsGraphAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::getMetricsLimitsGraphAction',
            ['request' => $request],
        );
    }

}
