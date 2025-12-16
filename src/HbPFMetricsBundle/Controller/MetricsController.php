<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Controller;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
     * @param MetricsHandler $handler
     */
    public function __construct(private readonly MetricsHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    #[Route('/metrics/topology/{topology}', methods: ['GET'])]
    public function topologyMetricsAction(Request $request, string $topology): Response
    {
        try {
            return $this->getResponse($this->handler->getTopologyMetrics($topology, $request->query->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $topology
     * @param string  $node
     *
     * @return Response
     */
    #[Route('/metrics/topology/{topology}/node/{node}', methods: ['GET'])]
    public function nodeMetricsAction(Request $request, string $topology, string $node): Response
    {
        try {
            return $this->getResponse($this->handler->getNodeMetrics($topology, $node, $request->query->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @return Response
     */
    #[Route('/metrics/healthcheck', methods: ['GET'])]
    public function healthcheckMetricsAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getHealthcheckMetrics());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    #[Route('/metrics/topology/{topology}/requests', methods: ['GET'])]
    public function topologyRequestsCountMetricsAction(Request $request, string $topology): Response
    {
        try {
            $dto = new GridRequestDto(Json::decode($request->query->get('filter', '{}')));

            return $this->getResponse(
                $this->handler->getRequestsCountMetrics($topology, $dto),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/connectors/overview', methods: [Request::METHOD_GET])]
    public function getMetricsConnectorsOverviewAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsConnectorsOverview(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/connectors', methods: [Request::METHOD_GET])]
    public function getMetricsConnectorsAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsConnectors(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/connectors/graph', methods: [Request::METHOD_GET])]
    public function getMetricsConnectorsGraphAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsConnectorsGraph(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/requests', methods: [Request::METHOD_GET])]
    public function getMetricsRequestsAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsRequests(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/processes', methods: [Request::METHOD_GET])]
    public function getMetricsProcessesAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsProcesses(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/limits', methods: [Request::METHOD_GET])]
    public function getMetricsLimitsAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsLimits(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/limits/total', methods: [Request::METHOD_GET])]
    public function getMetricsLimitsTotalAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsLimitsTotal(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/metrics/limits/graph', methods: [Request::METHOD_GET])]
    public function getMetricsLimitsGraphAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getMetricsLimitsGraph(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

}
