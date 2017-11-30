<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 11:25
 */

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MetricsController
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Controller
 * @Route(service="hbpf.metrics.controller.metrics")
 */
class MetricsController extends FOSRestController
{

    /**
     * @var MetricsHandler
     */
    private $metricsHandler;

    /**
     * @Route("/metrics/topology/{topologyName}")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topologyName
     *
     * @return JsonResponse
     */
    public function topologyMetricsAction(Request $request, string $topologyName): JsonResponse
    {
        $this->construct();
        $data = $this->metricsHandler->getTopologyMetrics($topologyName, $request->attributes->all());

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/metrics/topology/{topologyName}/node/{nodeName}")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topologyName
     *
     * @param string  $nodeName
     *
     * @return JsonResponse
     */
    public function nodeMetricsAction(Request $request, string $topologyName, string $nodeName): JsonResponse
    {
        $this->construct();
        $data = $this->metricsHandler->getNodeMetrics($topologyName, $nodeName, $request->attributes->all());

        return new JsonResponse($data);
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->metricsHandler) {
            $this->metricsHandler = $this->container->get('hbpf.metrics.handler.metrics');
        }
    }

}