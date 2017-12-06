<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 11:59 AM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StartingPointController
 *
 * @Route(service="hbpf.controller.starting_point")
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
class StartingPointController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var StartingPointHandler
     */
    private $handler;

    /**
     * @Route("/topologies/{topologyName}/nodes/{nodeName}/run", defaults={}, requirements={"topologyName": "\w+", "nodeName": "[\w-\.]+"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     *
     * @return Response
     */
    public function runAction(Request $request, string $topologyName, string $nodeName): Response
    {
        $this->construct();
        $this->handler->runWithRequest($request, $topologyName, $nodeName);

        return $this->getResponse([]);
    }

    /**
     * @Route("/topologies/{topologyId}/nodes/{nodeId}/run_by_id", defaults={}, requirements={"topologyId": "\w+", "nodeId": "[\w-\.]+"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string  $topologyId
     * @param string  $nodeId
     *
     * @return Response
     */
    public function runByIdAction(Request $request, string $topologyId, string $nodeId): Response
    {
        $this->construct();
        $this->handler->runWithRequestById($request, $topologyId, $nodeId);

        return $this->getResponse([]);
    }

    /**
     * @Route("/topologies/{topologyId}/test", defaults={}, requirements={"topologyId": "\w+"})
     * @Method({"GET"})
     *
     * @param string $topologyId
     *
     * @return Response
     */
    public function testAction(string $topologyId): Response
    {
        $this->construct();
        $data = $this->handler->runTest($topologyId);

        return $this->getResponse($data, 200, ['Content-Type' => 'application/json']);
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->handler) {
            $this->handler = $this->container->get('hbpf.handler.starting_point');
        }
    }

}