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
     * @Route("/topologies/{topologyName}/test", defaults={}, requirements={"topologyName": "\w+"})
     * @Method({"GET"})
     *
     * @param string $topologyName
     *
     * @return Response
     */
    public function testAction(string $topologyName): Response
    {
        return $this->forward('HbPFConfiguratorBundle:StartingPoint:test', ['topologyName' => $topologyName]);
    }

}