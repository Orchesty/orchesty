<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Exception\TopologyException;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\TopologyHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TopologyController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.controller.topology")
 */
class TopologyController extends FOSRestController
{

    /**
     * @var TopologyHandler
     */
    private $topologyHandler;

    /**
     * TopologyController constructor.
     *
     * @param TopologyHandler $topologyHandler
     */
    public function __construct(TopologyHandler $topologyHandler)
    {
        $this->topologyHandler = $topologyHandler;
    }

    /**
     * @Route("/topologies")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getTopologiesAction(Request $request): Response
    {
        $query = $request->query;
        $data  = $this->topologyHandler->getTopologies(
            $query->get('limit'),
            $query->get('offset'),
            $query->get('order_by')
        );

        return new JsonResponse($data, 200);
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
        try {
            return new JsonResponse($this->topologyHandler->getTopology($id));
        } catch (TopologyException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
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
        try {
            return new JsonResponse($this->topologyHandler->createTopology($request->request->all()));
        } catch (TopologyException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
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
        try {
            return new JsonResponse($this->topologyHandler->updateTopology($id, $request->request->all()));
        } catch (TopologyException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
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
        try {
            $response = new Response($this->topologyHandler->getTopologySchema($id));
            $response->headers->set('Content-Type', 'application/xml');

            return $response;
        } catch (TopologyException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
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
        try {
            /** @var string $content */
            $content = $request->getContent();

            return new JsonResponse($this->topologyHandler->saveTopologySchema(
                $id,
                $content,
                $request->request->all()
            ));
        } catch (TopologyException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/topologies/{id}/publish", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function publishTopologyAction(string $id): Response
    {
        $data = $this->topologyHandler->publishTopology($id);

        return new JsonResponse($data, 200);
    }

    /**
     * @Route("/topologies/{id}/clone", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function cloneTopologyAction(string $id): Response
    {
        $data = $this->topologyHandler->cloneTopology($id);

        return new JsonResponse($data, 200);
    }

    /**
     * @Route("/topologies/{id}/delete", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteTopologyAction(string $id): Response
    {
        $this->topologyHandler->deleteTopology($id);

        return new JsonResponse([], 200);
    }

}