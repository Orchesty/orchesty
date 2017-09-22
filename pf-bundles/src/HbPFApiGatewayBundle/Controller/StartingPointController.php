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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * @Route("/topologies/{topologyName}/nodes/{nodeName}/run", defaults={}, requirements={"topologyName": "\w+", "nodeName": "\w+"})
     * @Method({"POST"})
     *
     * @param string $topologyName
     * @param string $nodeName
     *
     * @return Response
     */
    public function runAction(string $topologyName, string $nodeName): Response
    {
        return $this->forward('HbPFConfiguratorBundle:StartingPoint:run',
            ['topologyName' => $topologyName, 'nodeName' => $nodeName]);
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
        return $this->forward('HbPFConfiguratorBundle:StartingPoint:test', ['topologyId' => $topologyId]);
    }

}