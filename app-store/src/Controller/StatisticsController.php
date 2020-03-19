<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Controller;

use Hanaboso\HbPFAppStore\Handler\StatisticsHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class StatisticsController
 *
 * @package Hanaboso\HbPFAppStore\Controller
 *
 * @Route("/statistics")
 */
final class StatisticsController
{

    use ControllerTrait;

    /**
     * @var StatisticsHandler
     */
    private StatisticsHandler $statisticsHandler;

    /**
     * StatisticsController constructor.
     *
     * @param StatisticsHandler $statisticsHandler
     */
    public function __construct(StatisticsHandler $statisticsHandler)
    {
        $this->statisticsHandler = $statisticsHandler;
    }

    /**
     * @Route("/applications", methods={"GET"})
     *
     * @return Response
     */
    public function getApplicationsBasicDataAction(): Response
    {
        try {
            return $this->getResponse($this->statisticsHandler->getApplicationsBasicData());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/applications/{application}", methods={"GET"})
     *
     * @param string $application
     *
     * @return Response
     */
    public function getApplicationsUsersAction(string $application): Response
    {
        try {
            return $this->getResponse($this->statisticsHandler->getApplicationsUsers($application));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

}
