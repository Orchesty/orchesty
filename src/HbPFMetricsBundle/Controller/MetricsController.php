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
use Symfony\Component\HttpFoundation\ParameterBag;
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
     * MetricsController constructor.
     *
     * @param MetricsHandler $metricsHandler
     */
    public function __construct(MetricsHandler $metricsHandler)
    {
        $this->metricsHandler = $metricsHandler;
    }

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
        try {
            /** @var ParameterBag $query */
            $query = $request->attributes->all()['query'];
            $data = $this->metricsHandler->getTopologyMetrics($topologyId, $query->all());
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
        try {
            /** @var ParameterBag $query */
            $query = $request->attributes->all()['query'];
            $data = $this->metricsHandler->getNodeMetrics($topologyId, $nodeId, $query->all());
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
        try {
            /** @var ParameterBag $query */
            $query = $request->attributes->all()['query'];
            $data = $this->metricsHandler->getRequestsCountMetrics($topologyId, $query->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
    }

}