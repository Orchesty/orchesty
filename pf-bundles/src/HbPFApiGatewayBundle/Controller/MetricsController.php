<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MetricsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class MetricsController extends AbstractController
{

    /**
     * @Route("/metrics/topology/{topology}", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    public function topologyMetricsAction(Request $request, string $topology): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyMetricsAction',
            ['request' => $request, 'topology' => $topology],
        );
    }

    /**
     * @Route("/metrics/topology/{topology}/node/{node}", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     * @param string  $node
     *
     * @return Response
     */
    public function nodeMetricsAction(Request $request, string $topology, string $node): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::nodeMetricsAction',
            ['request' => $request, 'topology' => $topology, 'node' => $node],
        );
    }

    /**
     * @Route("/metrics/consumers", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function consumerMetricsAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::consumerMetricsAction',
            ['request' => $request],
        );
    }

    /**
     * @Route("/metrics/topology/{topology}/requests", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    public function topologyRequestsCountMetricsAction(Request $request, string $topology): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyRequestsCountMetricsAction',
            ['request' => $request, 'topology' => $topology],
        );
    }

}
