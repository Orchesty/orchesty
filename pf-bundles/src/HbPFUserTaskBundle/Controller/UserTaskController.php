<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class UserTaskController
 *
 * @package Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller
 */
final class UserTaskController
{

    use ControllerTrait;

    /**
     * UserTaskController constructor.
     *
     * @param UserTaskHandler $handler
     */
    public function __construct(private UserTaskHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user-task', methods: ['GET'])]
    public function filterAction(Request $request): Response
    {
        try {
            return $this->getResponse(
                $this->handler->filter(new GridRequestDto(Json::decode($request->query->get('filter', '{}')))),
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}/accept', methods: ['POST'])]
    public function acceptAction(Request $request, string $id): Response
    {
        try {
            $topologyId = $request->get('topologyId');
            $nodeId     = $request->get('nodeId');

            return $this->getResponse($this->handler->accept($id, $topologyId, $nodeId));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user-task/accept', methods: ['POST'])]
    public function acceptBatchAction(Request $request): Response
    {
        try {
            $topologyId = $request->get('topologyId');
            $nodeId     = $request->get('nodeId');

            return $this->getResponse($this->handler->acceptBatch($request->toArray(),$topologyId, $nodeId));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}/reject', methods: ['POST'])]
    public function rejectAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->reject($id));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user-task/reject', methods: ['POST'])]
    public function rejectBatchAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->rejectBatch($request->toArray()));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}', methods: ['GET'])]
    public function getAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->get($id));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/user-task/{id}', methods: ['PUT'])]
    public function updateAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->handler->update($id, $request->toArray()));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

}
