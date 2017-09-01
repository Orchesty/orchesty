<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\TopologyHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Route;

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
        $this->topologyHandler->getTopologies($query->get('limit'), $query->get('offset'), $query->get('order_by'));

        return $this->handleView($this->view([]));
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
        $data = $this->topologyHandler->getTopology($id);

        return new JsonResponse($data, 200);
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
        $this->topologyHandler->updateTopology($id, $request->request->all());

        return $this->handleView($this->view([]));
    }

    /**
     * @Route("/topologies/{id}/scheme", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getTopologyScheme(string $id): Response
    {
        $this->topologyHandler->getTopologyScheme($id);

        return $this->handleView($this->view([]));
    }

    /**
     * @Route("/topologies/{id}/scheme", defaults={}, requirements={"id": "\w+"})
     * @Method({"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function uploadTopologyScheme(Request $request, string $id): Response
    {
        $this->topologyHandler->updateTopology($id, $request->request->all());

        return $this->handleView($this->view([]));
    }

}