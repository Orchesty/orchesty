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
final class MetricsController
{

    use ControllerTrait;

    /**
     * @var MetricsHandler
     */
    private MetricsHandler $metricsHandler;

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
     * @Route("/metrics/topology/{topology}", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    public function topologyMetricsAction(Request $request, string $topology): Response
    {
        try {
            return $this->getResponse($this->metricsHandler->getTopologyMetrics($topology, $request->query->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
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
        try {
            return $this->getResponse($this->metricsHandler->getNodeMetrics($topology, $node, $request->query->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
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
        try {
            return $this->getResponse(
                $this->metricsHandler->getRequestsCountMetrics($topology, $request->query->all())
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @Route("/metrics/application/{key}", methods={"GET", "OPTIONS"})
     *
     * @param Request     $request
     * @param string|null $key
     *
     * @return Response
     */
    public function applicationMetricsAction(Request $request, ?string $key = NULL): Response
    {
        try {
            return $this->getResponse(
                $this->metricsHandler->getApplicationMetrics($request->query->all(), $key)
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @Route("/metrics/user/{user}", methods={"GET", "OPTIONS"})
     *
     * @param Request     $request
     * @param string|null $user
     *
     * @return Response
     */
    public function userMetricsAction(Request $request, ?string $user = NULL): Response
    {
        try {
            return $this->getResponse(
                $this->metricsHandler->getUserMetrics($request->query->all(), $user)
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

}
