<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\ControllerTrait;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TopologyController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class TopologyController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @Route("/topologies", methods={"GET", "OPTIONS"})
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
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
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
     * @Route("/topologies", methods={"POST"})
     *
     * @return Response
     */
    public function createTopologyAction(): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Topology:createTopology');
    }

    /**
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PUT", "PATCH", "OPTIONS"})
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
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
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
     * @Route("/topologies/{id}/schema.bpmn", defaults={"_format"="xml"}, requirements={"id": "\w+"}, methods={"PUT", "OPTIONS"})
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
     * @Route("/topologies/{id}/publish", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
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
     * @Route("/topologies/{id}/clone", defaults={}, requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
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
     * @Route("/topologies/{id}", defaults={}, requirements={"id": "\w+"}, methods={"DELETE", "OPTIONS"})
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
