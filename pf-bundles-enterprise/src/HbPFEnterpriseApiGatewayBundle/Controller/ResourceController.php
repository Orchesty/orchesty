<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFrameworkEnterprise\Configurator\Handler\TopologyHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ResourceController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class ResourceController
{

    use ControllerTrait;

    /**
     * ResourceController constructor.
     *
     * @param TopologyHandler $handler
     */
    public function __construct(
        private readonly TopologyHandler $handler,
    )
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/nodes/connectors', methods: ['GET'], priority: 20)]
    public function getGroupedConnectorNodesAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getGroupedConnectorNodes());
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @return Response
     */
    #[Route('/resources/limiter/snapshot', methods: ['GET'], priority: 10)]
    public function limiterSnapshotAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getLimiterSnapshot());
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @return Response
     */
    #[Route('/resources/bridges', methods: ['GET'], priority: 10)]
    public function listBridgesAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getRunningBridges());
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string  $topologyId
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/resources/bridges/{topologyId}', methods: ['DELETE'], requirements: ['topologyId' => '[a-f0-9]{24}'], priority: 10)]
    public function decommissionBridgeAction(string $topologyId, Request $request): Response
    {
        try {
            $forceCleanup = $request->query->getBoolean('forceCleanup', FALSE);
            $this->handler->decommissionBridge($topologyId, $forceCleanup);

            return $this->getResponse([]);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $topologyId
     *
     * @return Response
     */
    #[Route('/resources/bridges/{topologyId}/restart', methods: ['POST'], requirements: ['topologyId' => '[a-f0-9]{24}'], priority: 10)]
    public function restartBridgeAction(string $topologyId): Response
    {
        try {
            return $this->getResponse($this->handler->restartBridge($topologyId));
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string  $topologyId
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/resources/bridges/{topologyId}/terminate', methods: ['POST'], requirements: ['topologyId' => '[a-f0-9]{24}'], priority: 10)]
    public function terminateProcessesAction(string $topologyId, Request $request): Response
    {
        try {
            $data          = Json::decode($request->getContent());
            $correlationId = $data['correlationId'] ?? NULL;

            return $this->getResponse($this->handler->terminateProcesses($topologyId, $correlationId));
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

}
