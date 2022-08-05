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
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class TrashController
 *
 * @package Hanaboso\Applinth\Controller
 *
 * @Route("/trash")
 */
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
     * @Route("", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getTrashItems(Request $request): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->filter($this->createDtoWithTrashFilter($request)));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateTrashItem(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->update($id, $request->toArray()));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/{id}/accept", methods={"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function acceptTrashItem(string $id): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->accept($id, NULL, NULL));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/{id}/reject", methods={"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function rejectTrashItem(string $id): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->reject($id));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/accept", methods={"GET"})
     *
     * @return Response
     */
    public function acceptTrashItems(): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->acceptBatch([UserTask::TYPE => 'trash']));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/reject", methods={"GET"})
     *
     * @return Response
     */
    public function rejectTrashItems(): Response
    {
        try {
            return $this->getResponse($this->userTaskHandler->rejectBatch([UserTask::TYPE => 'trash']));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/{id}", methods={"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
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
                        GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                        GridFilterAbstract::COLUMN   => UserTask::TYPE,
                        GridFilterAbstract::VALUE    => ['trash'],
                    ],
                ],
                [
                    [
                        GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                        GridFilterAbstract::COLUMN   => UserTask::USER,
                        GridFilterAbstract::VALUE    => [$this->authenticator->getAuthUser()],
                    ],
                ],
            ],
        );
    }

}
