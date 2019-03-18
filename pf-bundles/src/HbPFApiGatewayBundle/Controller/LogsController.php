<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogsController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class LogsController extends AbstractFOSRestController
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
