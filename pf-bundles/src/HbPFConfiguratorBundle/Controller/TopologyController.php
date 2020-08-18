<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class TopologyController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class TopologyController
{

    use ControllerTrait;

    /**
     * @var TopologyHandler
     */
    private TopologyHandler $topologyHandler;

    /**
     * TopologyController constructor.
     *
     * @param TopologyHandler $topologyHandler
     */
    public function __construct(TopologyHandler $topologyHandler)
    {
        $this->topologyHandler = $topologyHandler;
        $this->logger          = new NullLogger();
    }

    /**
     * @Route("/topologies", methods={"GET", "OPTIONS"})
     *
     * @param mixed $query
     *
     * @return Response
     */
    public function getTopologiesAction($query): Response
    {
        try {
            $limit  = $query->get('limit');
            $offset = $query->get('offset');
            $data   = $this->topologyHandler->getTopologies(
                isset($limit) ? (int) $limit : NULL,
                isset($offset) ? (int) $offset : NULL,
                $query->get('order_by')
            );

            return $this->getResponse($data);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/cron", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getCronTopologiesAction(): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->getCronTopologies());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getTopologyAction(string $id): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->getTopology($id));
        } catch (TopologyException | MongoDBException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createTopologyAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->createTopology($request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PUT", "PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateTopologyAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->updateTopology($id, $request->request->all()));
        } catch (TopologyException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getTopologySchemaAction(string $id): Response
    {
        try {
            $response = new Response($this->topologyHandler->getTopologySchema($id));
            $response->headers->set('Content-Type', 'application/xml');

            return $response;
        } catch (TopologyException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"}, methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function saveTopologySchemaAction(Request $request, string $id): Response
    {
        try {
            /** @var string $content */
            $content = $request->getContent();

            return $this->getResponse(
                $this->topologyHandler->saveTopologySchema(
                    $id,
                    $content,
                    $request->request->all()
                )
            );
        } catch (TopologyException | Throwable $e) {
            return $this->getErrorResponse(
                $e,
                in_array(
                    $e->getCode(),
                    [
                        TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND,
                        TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND,
                        TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST,
                        TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID,
                    ],
                    TRUE
                ) ? 400 : 500
            );
        }
    }

    /**
     * @Route("/topologies/{id}/publish", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function publishTopologyAction(string $id): Response
    {
        try {
            $res = $this->topologyHandler->publishTopology($id);

            return $this->getResponse($res->getBody(), $res->getStatusCode());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}/clone", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function cloneTopologyAction(string $id): Response
    {
        try {
            $data = $this->topologyHandler->cloneTopology($id);

            return $this->getResponse($data);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteTopologyAction(string $id): Response
    {
        try {
            $res = $this->topologyHandler->deleteTopology($id);

            return $this->getResponse($res->getBody(), $res->getStatusCode());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}/test", methods={"GET"})
     *
     * @param string $topologyId
     *
     * @return Response
     */
    public function testAction(string $topologyId): Response
    {
        try {
            $data = $this->topologyHandler->runTest($topologyId);

            return $this->getResponse($data, 200, ['Content-Type' => 'application/json']);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
