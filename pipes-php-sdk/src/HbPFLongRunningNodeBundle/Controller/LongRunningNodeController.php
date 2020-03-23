<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class LongRunningNodeController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller
 */
final class LongRunningNodeController
{

    use ControllerTrait;

    /**
     * @var LongRunningNodeHandler
     */
    private LongRunningNodeHandler $handler;

    /**
     * LongRunningNodeController constructor.
     *
     * @param LongRunningNodeHandler $handler
     */
    public function __construct(LongRunningNodeHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/longRunning/{id}/process", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processAction(Request $request, string $id): Response
    {
        try {
            $dto = $this->handler->process($id, $request->request->all(), $request->headers->all());

            return $this->getResponse($dto->getData(), 200, ControllerUtils::createHeaders($dto->getHeaders()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/{id}/process/test", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function testAction(string $id): Response
    {
        try {
            $this->handler->test($id);

            return $this->getResponse([]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/longRunning/id/topology/{topology}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    public function getTasksByIdAction(Request $request, string $topology): Response
    {
        try {
            return $this->getResponse(
                $this->handler->getTasksById(new GridRequestDto($request->headers->all()), $topology)
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/longRunning/name/topology/{topology}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     *
     * @return Response
     */
    public function getTasksAction(Request $request, string $topology): Response
    {
        try {
            return $this->getResponse(
                $this->handler->getTasks(new GridRequestDto($request->headers->all()), $topology)
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/id/topology/{topology}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     * @param string  $node
     *
     * @return Response
     */
    public function getNodeTasksByIdAction(Request $request, string $topology, string $node): Response
    {
        try {
            return $this->getResponse(
                $this->handler->getTasksById(new GridRequestDto($request->headers->all()), $topology, $node)
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/name/topology/{topology}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topology
     * @param string  $node
     *
     * @return Response
     */
    public function getNodeTasksAction(Request $request, string $topology, string $node): Response
    {
        try {
            return $this->getResponse(
                $this->handler->getTasks(
                    new GridRequestDto($request->headers->all()),
                    $topology,
                    $node
                )
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/list", methods={"GET"})
     *
     * @return Response
     */
    public function listOfLongRunningNodesAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getAllLongRunningNodes());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/longRunning/{id}", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateLongRunningAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->handler->updateLongRunningNode($id, $request->request->all()));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

}
