<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class NodeController extends FOSRestController
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

}