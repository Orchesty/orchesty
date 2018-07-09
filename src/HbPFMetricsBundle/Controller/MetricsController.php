<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 11:25
 */

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class MetricsController
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Controller
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
     * @Route("/metrics/topology/{topologyId}", methods={"GET", "OPTIONS"})
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
            $data  = $this->metricsHandler->getTopologyMetrics($topologyId, $query->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
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
        try {
            /** @var ParameterBag $query */
            $query = $request->attributes->all()['query'];
            $data  = $this->metricsHandler->getNodeMetrics($topologyId, $nodeId, $query->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
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
        try {
            /** @var ParameterBag $query */
            $query = $request->attributes->all()['query'];
            $data  = $this->metricsHandler->getRequestsCountMetrics($topologyId, $query->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
    }

}