<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Controller;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Hanaboso\Utils\String\Json;
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
     * MetricsController constructor.
     *
     * @param MetricsHandler $metricsHandler
     */
    public function __construct(private MetricsHandler $metricsHandler)
    {
        $this->logger = new NullLogger();
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
     * @Route("/metrics/consumers", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function consumerMetricsAction(Request $request): Response
    {
        try {
            $dto = new GridRequestDto(Json::decode($request->query->get('filter', '{}')));

            return $this->getResponse($this->metricsHandler->getConsumerMetrics($dto));
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
            $dto = new GridRequestDto(Json::decode($request->query->get('filter', '{}')));

            return $this->getResponse(
                $this->metricsHandler->getRequestsCountMetrics($topology, $dto),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

}
