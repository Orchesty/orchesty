<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Hanaboso\Applinth\Handler\StatisticsHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class StatisticsController
 *
 * @package Hanaboso\Applinth\Controller
 *
 * @Route("/statistics")
 */
final class StatisticsController
{

    use ControllerTrait;

    /**
     * StatisticsController constructor.
     *
     * @param StatisticsHandler $statisticsHandler
     */
    public function __construct(private StatisticsHandler $statisticsHandler)
    {
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
     * @Route("/users", methods={"GET"})
     *
     * @return Response
     */
    public function getUsersBasicDataAction(): Response
    {
        try {
            return $this->getResponse($this->statisticsHandler->getUsersBasicData());
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

    /**
     * @Route("/applications/application/{key}", methods={"GET", "OPTIONS"})
     *
     * @param Request     $request
     * @param string|null $key
     *
     * @return Response
     */
    public function applicationStatisticsAction(Request $request, ?string $key): Response
    {
        try {
            return $this->getResponse(
                $this->statisticsHandler->getApplicationMetrics($request->query->all(), $key),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @Route("/applications/user/{user}", methods={"GET", "OPTIONS"})
     *
     * @param Request     $request
     * @param string|null $user
     *
     * @return Response
     */
    public function userStatisticsAction(Request $request, ?string $user): Response
    {
        try {
            return $this->getResponse(
                $this->statisticsHandler->getUserMetrics($request->query->all(), $user),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

}
