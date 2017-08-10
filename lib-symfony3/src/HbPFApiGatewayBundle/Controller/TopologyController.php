<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGateway\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFApiGateway\Handler\TopologyHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TopologyController
 * @package Hanaboso\PipesFramework\HbPFApiGateway\Controller
 */
class TopologyController extends FOSRestController
{

    /**
     * @Route("/topology/{id}/user_actions", defaults={}, requirements={"id": "\w+"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getTopologiesAction(Request $request): Response
    {
        /** @var TopologyHandler $topologyHandler */
        $topologyHandler = $this->container->get('hbpf.handler.topology');

        $result = $topologyHandler->getTopologies();

        return $this->handleView($this->view($result));
    }

}