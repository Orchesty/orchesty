<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TopologyController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.topology")
 */
class TopologyController extends FOSRestController
{

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
        return $this->forward('HbPFConfiguratorBundle:Topology:getTopologies', ['query' => $request->query]);
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
        return $this->forward('HbPFConfiguratorBundle:Topology:getTopology', ['id' => $id]);
    }

    /**
     * @Route("/topologies")
     * @Method({"POST"})
     *
     * @return Response
     */
    public function createTopologyAction(): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Topology:createTopology');
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"PUT", "PATCH", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function updateTopologyAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Topology:updateTopology', ['id' => $id]);
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
        return $this->forward('HbPFConfiguratorBundle:Topology:getTopologySchema', ['id' => $id]);
    }

    /**
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"})
     * @Method({"PUT", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function saveTopologySchemaAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Topology:saveTopologySchema', ['id' => $id]);
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
        return $this->forward('HbPFConfiguratorBundle:Topology:publishTopology', ['id' => $id]);
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
        return $this->forward('HbPFConfiguratorBundle:Topology:cloneTopology', ['id' => $id]);
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
        return $this->forward('HbPFConfiguratorBundle:Topology:deleteTopology', ['id' => $id]);
    }

}