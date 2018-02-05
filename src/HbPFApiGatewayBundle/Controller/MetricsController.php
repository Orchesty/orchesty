<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.metrics")
 */
class MetricsController extends FOSRestController
{

    /**
     * @Route("/metrics/topology/{topologyId}")
     * @Method({"GET", "OPTIONS"})
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
     * @Route("/metrics/topology/{topologyId}/node/{nodeId}")
     * @Method({"GET", "OPTIONS"})
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
     * @Route("/metrics/topology/{topologyId}/requests")
     * @Method({"GET", "OPTIONS"})
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