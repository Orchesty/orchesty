<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/19/18
 * Time: 1:27 PM
 */

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LogsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\
 */
class LogsController extends FOSRestController
{

    /**
     * @Route("/logs")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function topologyMetricsAction(Request $request): Response
    {
        return $this->forward('HbPFLogsBundle:Logs:getDataForTable', [], $request->query->all());
    }

}