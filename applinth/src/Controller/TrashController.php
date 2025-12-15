<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class TrashController
 *
 * @package Hanaboso\Applinth\Controller
 */
#[Route('/trash')]
final class TrashController extends AbstractController
{

    use ControllerTrait;

    /**
     * TrashController constructor.
     *
     * @param EndUserAuthenticator $authenticator
     * @param UserTaskHandler      $userTaskHandler
     */
    public function __construct(
        private readonly EndUserAuthenticator $authenticator,
        private readonly UserTaskHandler $userTaskHandler,
    )
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('', methods: ['GET'])]
    public function getTrashItems(Request $request): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->filter($this->createDtoWithTrashFilter($request)));
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
    #[Route('/{id}', methods: ['PUT'])]
    public function updateTrashItem(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->update($id, $request->toArray()));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/{id}/accept', methods: ['POST'])]
    public function acceptTrashItem(string $id): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->accept($id, NULL, NULL));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/{id}/reject', methods: ['POST'])]
    public function rejectTrashItem(string $id): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->reject($id));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/accept', methods: ['POST'])]
    public function acceptTrashItems(Request $request): Response
    {
        try {
            $ids    = $request->request->all()[UserTaskHandler::IDS] ?? [];
            $filter = [];
            if ($ids !== []) {
                $filter = [UserTaskHandler::IDS => $ids];
            }

            return $this->getResponse(
                $this->userTaskHandler->acceptBatch(array_merge([UserTask::TYPE => 'trash'], $filter)),
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/reject', methods: ['POST'])]
    public function rejectTrashItems(Request $request): Response
    {
        try {
            $ids    = $request->request->all()[UserTaskHandler::IDS] ?? [];
            $filter = [];
            if ($ids !== []) {
                $filter = [UserTaskHandler::IDS => $ids];
            }

            return $this->getResponse(
                $this->userTaskHandler->rejectBatch(array_merge([UserTask::TYPE => 'trash'], $filter)),
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/{id}', methods: ['GET'])]
    public function getTrashItemDetail(string $id): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->get($id));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return GridRequestDto
     * @throws GridException
     */
    private function createDtoWithTrashFilter(Request $request): GridRequestDto
    {
        $gridRequestDto = new GridRequestDto(Json::decode($request->query->get('filter', '{}')));

        return $gridRequestDto->setAdditionalFilters(
            [
                [
                    [
                        GridFilterAbstract::COLUMN   => UserTask::TYPE,
                        GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                        GridFilterAbstract::VALUE    => ['trash'],
                    ],
                ],
                [
                    [
                        GridFilterAbstract::COLUMN   => UserTask::USER,
                        GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                        GridFilterAbstract::VALUE    => [$this->authenticator->getAuthUser()],
                    ],
                ],
            ],
        );
    }

}
