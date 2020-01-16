<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
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
class LongRunningNodeController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var LongRunningNodeHandler
     */
    private $handler;

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
     * @Route("/longRunning/{nodeId}/process", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $nodeId
     *
     * @return Response
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    public function processAction(Request $request, string $nodeId): Response
    {
        try {
            $data = $this->handler->process($nodeId, $request->request->all(), $request->headers->all());

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/{nodeId}/process/test", methods={"GET", "OPTIONS"})
     *
     * @param string $nodeId
     *
     * @return Response
     */
    public function testAction(string $nodeId): Response
    {
        try {
            $this->handler->test($nodeId);

            return $this->getResponse([]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/longRunning/id/topology/{topo}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topo
     *
     * @return Response
     */
    public function getTasksByIdAction(Request $request, string $topo): Response
    {
        try {
            return $this->getResponse(
                $this->handler->getTasksById(
                    new GridRequestDto($request->headers->all()),
                    $topo
                )
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/name/topology/{topo}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topo
     *
     * @return Response
     */
    public function getTasksAction(Request $request, string $topo): Response
    {
        try {
            return $this->getResponse($this->handler->getTasks(new GridRequestDto($request->headers->all()), $topo));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/id/topology/{topo}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topo
     * @param string  $node
     *
     * @return Response
     */
    public function getNodeTasksByIdAction(Request $request, string $topo, string $node): Response
    {
        try {
            return $this->getResponse(
                $this->handler->getTasksById(
                    new GridRequestDto($request->headers->all()),
                    $topo,
                    $node
                )
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/longRunning/name/topology/{topo}/node/{node}/getTasks", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $topo
     * @param string  $node
     *
     * @return Response
     */
    public function getNodeTasksAction(Request $request, string $topo, string $node): Response
    {
        try {
            return $this->getResponse(
                $this->handler->getTasks(
                    new GridRequestDto($request->headers->all()),
                    $topo,
                    $node
                )
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
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
            $data = $this->handler->getAllLongRunningNodes();

            return $this->getResponse($data);
        } catch (Throwable $t) {
            return $this->getErrorResponse($t, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
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
