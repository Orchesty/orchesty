<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 *
 * @Route(service="hbpf.configurator.controller.node")
 */
class NodeController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var NodeHandler
     */
    private $nodeHandler;

    /**
     * @Route("/topologies/{id}/nodes", defaults={}, requirements={"id": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodesAction(string $id): Response
    {
        $this->construct();
        $data = $this->nodeHandler->getNodes($id);

        return $this->getResponse($data);
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
        $this->construct();
        $data = $this->nodeHandler->getNode($id);

        return $this->getResponse($data);
    }

    /**
     * @Route("/nodes/{id}/{request}", defaults={}, requirements={"id": "\w+"})
     * @Method({"PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateNodeAction(Request $request, string $id): Response
    {
        $this->construct();
        $data = $this->nodeHandler->updateNode($id, $request->request->all());

        return $this->getResponse($data);
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->nodeHandler) {
            $this->nodeHandler = $this->container->get('hbpf.configurator.handler.node');
        }
    }

}