<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\DashboardHandler;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DashboardController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class DashboardController
{

    use ControllerTrait;

    /**
     * DashboardController constructor.
     *
     * @param DashboardHandler $dashboardHandler
     */
    public function __construct(private DashboardHandler $dashboardHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @Route("/dashboards/default", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws DateTimeException
     */
    public function getDashboardAction(Request $request): Response
    {
        $range = $request->get('range', '24h');

        return $this->getResponse(Json::encode($this->dashboardHandler->getMetrics($range)));
    }

}
