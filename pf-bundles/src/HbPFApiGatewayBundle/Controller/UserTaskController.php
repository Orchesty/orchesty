<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserTaskController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class UserTaskController extends AbstractController
{

    /**
     * @Route("/user-task", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function filterAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::filterAction',
            [],
            $request->query->all(),
        );
    }

    /**
     * @Route("/user-task/{id}/accept", methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function acceptAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/user-task/accept", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function accpetBatchAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptBatchAction',
            ['request' => $request],
        );
    }

    /**
     * @Route("/user-task/{id}/reject", methods={"POST", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function rejectAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/user-task/reject", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function rejectBatchAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectBatchAction',
            ['request' => $request],
        );
    }

    /**
     * @Route("/user-task/{id}", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::getAction',
            ['id' => $id],
        );
    }

    /**
     * @Route("/user-task/{id}", methods={"PUT", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function updateAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::updateAction',
            ['id' => $id],
        );
    }

}
