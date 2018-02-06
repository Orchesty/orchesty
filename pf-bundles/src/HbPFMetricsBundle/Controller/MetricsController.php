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
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class MetricsController
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Controller
 * @Route(service="hbpf.metrics.controller.metrics")
 */
class MetricsController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var MetricsHandler
     */
    private $metricsHandler;

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
        $this->construct();
        try {
            $data = $this->metricsHandler->getTopologyMetrics($topologyId, $request->attributes->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
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
        $this->construct();
        try {
            $data = $this->metricsHandler->getNodeMetrics($topologyId, $nodeId, $request->attributes->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
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
        $this->construct();
        try {
            $data = $this->metricsHandler->getRequestsCountMetrics($topologyId, $request->attributes->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
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