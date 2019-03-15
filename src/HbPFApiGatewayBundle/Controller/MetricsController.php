<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MetricsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class MetricsController extends FOSRestController
{

    /**
     * @Route("/metrics/topology/{topologyId}", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topologyId
     *
     * @return Response
     */
    public function topologyMetricsAction(Request $request, string $topologyId): Response
    {
        return $this->forward('HbPFMetricsBundle:Metrics:topologyMetrics',
            ['query' => $request->query, 'topologyId' => $topologyId]
        );
    }

    /**
     * @Route("/metrics/topology/{topologyId}/node/{nodeId}", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topologyId
     * @param string  $nodeId
     *
     * @return Response
     */
    public function nodeMetricsAction(Request $request, string $topologyId, string $nodeId): Response
    {
        return $this->forward('HbPFMetricsBundle:Metrics:nodeMetrics',
            ['query' => $request->query, 'topologyId' => $topologyId, 'nodeId' => $nodeId]
        );
    }

    /**
     * @Route("/metrics/topology/{topologyId}/requests", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topologyId
     *
     * @return Response
     */
    public function topologyRequestsCountMetricsAction(Request $request, string $topologyId): Response
    {
        return $this->forward('HbPFMetricsBundle:Metrics:topologyRequestsCountMetrics',
            ['query' => $request->query, 'topologyId' => $topologyId]
        );
    }

}
