<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/19/18
 * Time: 1:27 PM
 */

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\
 */
class LogsController extends FOSRestController
{

    /**
     * @Route("/logs", methods={"GET"})
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