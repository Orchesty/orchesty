<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class UserTaskController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class UserTaskController extends AbstractController
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user-task', methods: ['GET'])]
    public function filterAction(Request $request): Response
    {
        // TODO RB: Remove this after new UI
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::filterAction',
            [],
            $request->query->all(),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user-tasks', methods: ['GET'])]
    public function getUserTasksAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::getUserTasksAction',
            [],
            $request->query->all(),
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}/accept', methods: ['POST'])]
    public function acceptAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user-task/accept', methods: ['POST'])]
    public function accpetBatchAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptBatchAction',
            ['request' => $request],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}/reject', methods: ['POST'])]
    public function rejectAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user-task/reject', methods: ['POST'])]
    public function rejectBatchAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectBatchAction',
            ['request' => $request],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}', methods: ['GET'])]
    public function getAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::getAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}', methods: ['PUT'])]
    public function updateAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::updateAction',
            ['id' => $id],
        );
    }

}
