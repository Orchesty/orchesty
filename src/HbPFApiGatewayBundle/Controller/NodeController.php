<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\NodeHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.node")
 */
class NodeController extends FOSRestController
{

    /**
     * @var NodeHandler
     */
    private $nodeHandler;

    /**
     * NodeController constructor.
     *
     * @param NodeHandler $nodeHandler
     */
    public function __construct(NodeHandler $nodeHandler)
    {
        $this->nodeHandler = $nodeHandler;
    }

    /**
     * @Route("/topologies/{id}/events", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodesAction(string $id): Response
    {
        $data = $this->nodeHandler->getNodes($id);

        return new JsonResponse($data, 200);
    }

    /**
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodeAction(string $id): Response
    {
        $data = $this->nodeHandler->getNode($id);

        return new JsonResponse($data, 200);
    }

    /**
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateNodeAction(Request $request, string $id): Response
    {
        $data = $this->nodeHandler->updateNode($id, $request->request->all());

        return new JsonResponse($data, 200);
    }

}