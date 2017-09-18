<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 11:59 AM
 */

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\StartingPointHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StartingPointController
 *
 * @Route(service="hbpf.api_gateway.controller.starting_point")
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class StartingPointController extends FOSRestController
{

    /**
     * @var StartingPointHandler
     */
    private $startingPointHandler;

    /**
     * StartingPointController constructor.
     *
     * @param StartingPointHandler $startingPointHandler
     */
    public function __construct(StartingPointHandler $startingPointHandler)
    {
        $this->startingPointHandler = $startingPointHandler;
    }

    /**
     * @Route("/topologies/{topologyId}/nodes/{nodeId}/run", defaults={}, requirements={"topologyId": "\w+", "nodeId": "\w+"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string  $topologyId
     * @param string  $nodeId
     *
     * @return Response
     */
    public function runAction(Request $request, string $topologyId, string $nodeId): Response
    {
        $this->startingPointHandler->runWithRequest($request, $topologyId, $nodeId);

        return $this->handleView($this->view([], 200, []));
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
        $data = $this->startingPointHandler->runTest($topologyId);

        return $this->handleView($this->view($data, 200, ['application/json']));
    }

}