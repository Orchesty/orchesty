<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class NodeController extends AbstractFOSRestController
{

    /**
     * @Route("/topologies/{id}/nodes", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodesAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Node:getNodes', ['id' => $id]);
    }

    /**
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodeAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Node:getNode', ['id' => $id]);
    }

    /**
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PATCH", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function updateNodeAction(string $id): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Node:updateNode', ['id' => $id]);
    }

    /**
     * @Route("/nodes/{type}/list_nodes", requirements={"type"="connector|custom_node|joiner|mapper"}, methods={"GET"})
     *
     * @param string $type
     *
     * @return Response
     */
    public function listOfNodesAction(string $type)
    {
        switch ($type) {
            case 'connector':
                return $this->forward('HbPFConnectorBundle:Connector:listOfConnectors');
                break;
            case 'custom_node':
                return $this->forward('HbPFCustomNodeBundle:CustomNode:listOfCustomNodes');
                break;
            case 'joiner':
                return $this->forward('HbPFCustomNodeBundle:CustomNode:listOfCustomNodes');
                break;
            case 'mapper':
                return $this->forward('HbPFMapperBundle:Mapper:listOfMappers');
                break;
        }
    }

}
