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
     * TopologyController constructor.
     *
     * @param TopologyHandler $topologyHandler
     */
    public function __construct(private TopologyHandler $topologyHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param mixed $query
     *
     * @return Response
     */
    #[Route('/topologies', methods: ['GET'])]
    public function getTopologiesAction(mixed $query): Response
    {
        try {
            $limit  = $query->get('limit');
            $offset = $query->get('offset');
            $data   = $this->topologyHandler->getTopologies(
                isset($limit) ? (int) $limit : NULL,
                isset($offset) ? (int) $offset : NULL,
                $query->get('order_by'),
            );

            return $this->getResponse($data);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/run', methods: ['POST'])]
    public function runTopologiesAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->runTopology($id, $request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     *
     * @return Response
     */
    #[Route('/topologies/{topologyName}/nodes/{nodeName}/run-by-name', methods: ['POST'])]
    public function runTopologyByNameAction(Request $request, string $topologyName, string $nodeName): Response
    {
        try {
            return $this->getResponse(
                $this->topologyHandler->runTopologyByName($topologyName, $nodeName, $request->request->all()),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @return Response
     */
    #[Route('/topologies/cron', methods: ['GET'])]
    public function getCronTopologiesAction(): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->getCronTopologies());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}', requirements: ['id' => '\w+'], methods: ['GET'])]
    public function getTopologyAction(string $id): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->getTopology($id));
        } catch (TopologyException|MongoDBException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/topologies', methods: ['POST'])]
    public function createTopologyAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->createTopology($request->request->all()));
        } catch (TopologyException $e) {
            return match ($e->getCode()) {
                TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND, TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND => $this->getErrorResponse(
                    $e,
                    404,
                ),
                default => $this->getErrorResponse($e, 400),
            };
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}', requirements: ['id' => '\w+'], methods: ['PUT', 'PATCH'])]
    public function updateTopologyAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->topologyHandler->updateTopology($id, $request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/{id}/schema.bpmn',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'xml'],
        methods: ['GET'],
    )]
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
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/{id}/schema.bpmn',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'xml'],
        methods: ['PUT'],
    )]
    public function saveTopologySchemaAction(Request $request, string $id): Response
    {
        try {
            /** @var string $content */
            $content = $request->getContent();

            return $this->getResponse(
                $this->topologyHandler->saveTopologySchema(
                    $id,
                    $content,
                    $request->request->all(),
                ),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse(
                $e,
                in_array(
                    $e->getCode(),
                    [
                        TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND,
                        TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND,
                        TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST,
                        TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID,
                        TopologyException::UNSUPPORTED_SCHEMA,
                    ],
                    TRUE,
                ) ? 400 : 500,
            );
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route(
        '/topologies/check/{id}/schema.bpmn',
        requirements: ['id' => '\w+'],
        defaults: ['_format' => 'xml'],
        methods: ['POST'],
    )]
    public function checkTopologySchemaDifferencesAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse(
                $this->topologyHandler->checkTopologySchemaDifferences(
                    $id,
                    $request->request->all(),
                ),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse(
                $e,
                in_array(
                    $e->getCode(),
                    [
                        TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND,
                        TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND,
                        TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST,
                        TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID,
                        TopologyException::UNSUPPORTED_SCHEMA,
                    ],
                    TRUE,
                ) ? 400 : 500,
            );
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/publish', requirements: ['id' => '\w+'], methods: ['POST'])]
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
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/clone', requirements: ['id' => '\w+'], methods: ['POST'])]
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
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteTopologyAction(Request $request, string $id): Response
    {
        try {
            $removeWithTasks = $request->get('removeWithTasks');
            $res             = $this->topologyHandler->deleteTopology($id, $removeWithTasks);

            return $this->getResponse($res->getBody(), $res->getStatusCode());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $topologyId
     *
     * @return Response
     */
    #[Route('/topologies/{id}/test', methods: ['GET'])]
    public function testAction(string $topologyId): Response
    {
        try {
            $data = $this->topologyHandler->runTest($topologyId);

            return $this->getResponse($data, 200, ['Content-Type' => 'application/json']);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $topologyId
     * @param string $nodeName
     *
     * @return Response
     */
    #[Route('/topologies/{topologyId}/versions/node/{nodeName}', methods: ['GET'])]
    public function getTopologyVersions(string $topologyId, string $nodeName): Response
    {
        try {
            if ($topologyId && $nodeName) {
                $data = $this->topologyHandler->getTopologiesByIdAndNodeName($topologyId, $nodeName);

                return $this->getResponse($data, 200, ['Content-Type' => 'application/json']);
            } else {
                return $this->getResponse(
                    sprintf('Parameter topologyId [%s] or nodeName [%s]is empty', $topologyId, $nodeName),
                    400,
                    ['Content-Type' => 'application/json'],
                );
            }
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
