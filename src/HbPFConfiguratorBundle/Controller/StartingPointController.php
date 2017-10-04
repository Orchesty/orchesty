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

    /**
     * @var StartingPointHandler
     */
    private $handler;

    /**
     * @Route("/topologies/{topologyName}/nodes/{nodeName}/run", defaults={}, requirements={"topologyName": "\w+", "nodeName": "\w+"})
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

        return $this->handleView($this->view([], 200, []));
    }

    /**
     * @Route("/topologies/{topologyName}/test", defaults={}, requirements={"topologyName": "\w+"})
     * @Method({"GET"})
     *
     * @param string $topologyName
     *
     * @return Response
     */
    public function testAction(string $topologyName): Response
    {
        $this->construct();
        $data = $this->handler->runTest($topologyName);

        return $this->handleView($this->view($data, 200, ['application/json']));
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