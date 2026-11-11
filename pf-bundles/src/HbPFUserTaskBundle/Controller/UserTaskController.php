<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskAnswerException;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskConflictException;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskException;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskUnauthorizedException;
use Hanaboso\UserBundle\Document\UserAbstract;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\JsonException;
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
     * @param Security        $security
     */
    public function __construct(private readonly UserTaskHandler $handler, private readonly Security $security)
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
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/user-tasks', methods: [Request::METHOD_GET])]
    public function getUserTasksAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getUserTasks(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
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
            $topologyId = $request->request->getString('topologyId');
            $nodeId     = $request->request->getString('nodeId');

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
            return $this->getResponse($this->handler->acceptBatch($request->toArray()));
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

    /**
     * @return Response
     */
    #[Route('/pulse/user-tasks/pending', methods: ['GET'])]
    public function pulsePendingAction(): Response
    {
        try {
            return $this->getResponse($this->handler->pendingConfirms($this->getIdentity()));
        } catch (Throwable $t) {
            return $this->getPulseErrorResponse($t);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/pulse/user-task/{id}/accept', methods: ['POST'])]
    public function pulseAcceptAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->pulseDecide($id, $this->getIdentity(), TRUE));
        } catch (Throwable $t) {
            return $this->getPulseErrorResponse($t);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/pulse/user-task/{id}/reject', methods: ['POST'])]
    public function pulseRejectAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->pulseDecide($id, $this->getIdentity(), FALSE));
        } catch (Throwable $t) {
            return $this->getPulseErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/pulse/user-task/{id}/answer', methods: ['POST'])]
    public function pulseAnswerAction(Request $request, string $id): Response
    {
        try {
            $answer = $request->toArray()['answer'] ?? NULL;
            if (!is_array($answer)) {
                throw new UserTaskAnswerException('Request body must contain an "answer" object.');
            }

            return $this->getResponse($this->handler->pulseAnswer($id, $answer, $this->getIdentity()));
        } catch (JsonException | UserTaskAnswerException $e) {
            return $this->getErrorResponse($e, 400, ControllerUtils::INVALID_REQUEST);
        } catch (Throwable $t) {
            return $this->getPulseErrorResponse($t);
        }
    }

    /**
     * A client-supplied identity is never trusted.
     *
     * @return string
     * @throws UserTaskUnauthorizedException
     */
    private function getIdentity(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserAbstract || $user->getEmail() === '') {
            throw new UserTaskUnauthorizedException('Not logged in.');
        }

        return $user->getEmail();
    }

    /**
     * @param Throwable $t
     *
     * @return Response
     */
    private function getPulseErrorResponse(Throwable $t): Response
    {
        return match (TRUE) {
            $t instanceof UserTaskUnauthorizedException => $this->getErrorResponse(
                $t,
                401,
                ControllerUtils::NOT_LOGGED,
            ),
            $t instanceof UserTaskException => $this->getErrorResponse($t, 404, ControllerUtils::NOT_FOUND),
            $t instanceof UserTaskConflictException => $this->getErrorResponse(
                $t,
                409,
                ControllerUtils::INVALID_REQUEST,
            ),
            default => $this->getErrorResponse($t),
        };
    }

}
