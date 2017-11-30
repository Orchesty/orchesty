<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.user")
 */
class MetricsController extends FOSRestController
{

    /**
     * @Route("/metrics/topology/{topologyName}")
     * @Method({"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function topologyMetricsAction(): Response
    {
        return $this->forward('HbPFMetricsBundle:Metrics:topologyMetrics');
    }

    /**
     * @Route("/api/metrics/topology/{topologyName}/node/{nodeName}")
     * @Method({"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function nodeMetricsAction(): Response
    {
        return $this->forward('HbPFMetricsBundle:Metrics:nodeMetrics');
    }

}