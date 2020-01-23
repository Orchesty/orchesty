<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Controller;

use Exception;
use Hanaboso\HbPFAppStore\Handler\StatisticsHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class StatisticsController
 *
 * @Route("/statistics")
 *
 * @package Hanaboso\HbPFAppStore\Controller
 */
class StatisticsController
{

    use ControllerTrait;

    /**
     * @var StatisticsHandler
     */
    private $statisticsHandler;

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
     *
     * @return Response
     */
    public function getApplicationsBasicDataAction(): Response
    {
        try {
            $data = $this->statisticsHandler->getApplicationsBasicData();

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

    /**
     * @Route("/applications/{application}", methods={"GET"})
     * @param string $application
     *
     * @return Response
     */
    public function getApplicationsUsersAction(string $application): Response
    {
        try {
            $data = $this->statisticsHandler->getApplicationsUsers($application);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

}
