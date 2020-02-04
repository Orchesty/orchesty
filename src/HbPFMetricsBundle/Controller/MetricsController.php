<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Controller;

use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class MetricsController
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Controller
 */
class MetricsController
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
        $this->logger         = new NullLogger();
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
            $data = $this->metricsHandler->getTopologyMetrics($topologyId, $request->query->all());
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
            $data = $this->metricsHandler->getNodeMetrics($topologyId, $nodeId, $request->query->all());
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
            $data = $this->metricsHandler->getRequestsCountMetrics($topologyId, $request->query->all());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }

        return $this->getResponse($data);
    }

}
