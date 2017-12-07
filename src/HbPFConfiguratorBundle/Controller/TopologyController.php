<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TopologyController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 *
 * @Route(service="hbpf.configurator.controller.topology")
 */
class TopologyController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var TopologyHandler
     */
    private $topologyHandler;

    /**
     * @Route("/topologies")
     * @Method({"GET", "OPTIONS"})
     *
     * @param mixed $query
     *
     * @return Response
     */
    public function getTopologiesAction($query): Response
    {
        $this->construct();
        $limit  = $query->get('limit');
        $offset = $query->get('offset');
        $data   = $this->topologyHandler->getTopologies(
            isset($limit) ? (int) $limit : NULL,
            isset($offset) ? (int) $offset : NULL,
            $query->get('order_by')
        );

        return $this->getResponse($data);
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getTopologyAction(string $id): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->topologyHandler->getTopology($id));
        } catch (TopologyException $e) {
            return $this->getErrorResponse($e);
        }

    }

    /**
     * @Route("/topologies", requirements={"id": "\w+"})
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createTopologyAction(Request $request): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->topologyHandler->createTopology($request->request->all()));
        } catch (TopologyException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"PUT", "PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateTopologyAction(Request $request, string $id): Response
    {
        $this->construct();
        try {
            return $this->getResponse($this->topologyHandler->updateTopology($id, $request->request->all()));
        } catch (TopologyException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getTopologySchemaAction(string $id): Response
    {
        $this->construct();
        try {
            $response = new Response($this->topologyHandler->getTopologySchema($id));
            $response->headers->set('Content-Type', 'application/xml');

            return $response;
        } catch (TopologyException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"})
     * @Method({"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function saveTopologySchemaAction(Request $request, string $id): Response
    {
        $this->construct();
        try {
            /** @var string $content */
            $content = $request->getContent();

            return $this->getResponse($this->topologyHandler->saveTopologySchema(
                $id,
                $content,
                $request->request->all()
            ));
        } catch (TopologyException $e) {
            return $this->getErrorResponse($e,
                in_array($e->getCode(), [
                    TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND,
                    TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND,
                    TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST,
                    TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID,
                ], TRUE) ? 400 : 500
            );
        }
    }

    /**
     * @Route("/topologies/{id}/publish", defaults={}, requirements={"id": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function publishTopologyAction(string $id): Response
    {
        $this->construct();
        $res = $this->topologyHandler->publishTopology($id);

        return $this->getResponse($res->getBody(), $res->getStatusCode());
    }

    /**
     * @Route("/topologies/{id}/clone", defaults={}, requirements={"id": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function cloneTopologyAction(string $id): Response
    {
        $this->construct();
        $data = $this->topologyHandler->cloneTopology($id);

        return $this->getResponse($data);
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteTopologyAction(string $id): Response
    {
        $this->construct();
        $res = $this->topologyHandler->deleteTopology($id);

        return $this->getResponse($res->getBody(), $res->getStatusCode());
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->topologyHandler) {
            $this->topologyHandler = $this->container->get('hbpf.configurator.handler.topology');
        }
    }

}