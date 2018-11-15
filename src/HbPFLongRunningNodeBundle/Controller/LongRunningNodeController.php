<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class LongRunningNodeController
 *
 * @package Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Controller
 */
class LongRunningNodeController extends FOSRestController
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
     * @Route("/longRunning/run/id/topology/{topoId}/node/{nodeId}", methods={"GET", "POST", "OPTIONS"})
     * @Route("/longRunning/run/id/topology/{topoId}/node/{nodeId}/token/{token}", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request     $request
     * @param string      $topoId
     * @param string      $nodeId
     * @param null|string $token
     *
     * @return Response
     */
    public function runByIdAction(Request $request, string $topoId, string $nodeId, ?string $token = NULL): Response
    {
        try {
            $res = $this->handler->runById($topoId, $nodeId, $request->request->all() ?? '', $token);

            return $this->getResponse($res, 200);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders([], $e));
        }
    }

    /**
     * @Route("/longRunning/run/name/topology/{topoName}/node/{nodeName}", methods={"GET", "POST", "OPTIONS"})
     * @Route("/longRunning/run/name/topology/{topoName}/node/{nodeName}/token/{token}", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request     $request
     * @param string      $topoName
     * @param string      $nodeName
     * @param null|string $token
     *
     * @return Response
     */
    public function runAction(Request $request, string $topoName, string $nodeName, ?string $token = NULL): Response
    {
        try {
            $res = $this->handler->run($topoName, $nodeName, $request->request->all() ?? '', $token);

            return $this->getResponse($res, 200);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders([], $e));
        }
    }

    /**
     * @Route("/longRunning/stop/id/topology/{topoId}/node/{nodeId}", methods={"GET", "POST", "OPTIONS"})
     * @Route("/longRunning/stop/id/topology/{topoId}/node/{nodeId}/token/{token}", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request     $request
     * @param string      $topoId
     * @param string      $nodeId
     * @param null|string $token
     *
     * @return Response
     */
    public function stopByIdAction(Request $request, string $topoId, string $nodeId, ?string $token = NULL): Response
    {
        try {
            $res = $this->handler->runById($topoId, $nodeId, $request->request->all() ?? '', $token, TRUE);

            return $this->getResponse($res, 200);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders([], $e));
        }
    }

    /**
     * @Route("/longRunning/stop/name/topology/{topoName}/node/{nodeName}", methods={"GET", "POST", "OPTIONS"})
     * @Route("/longRunning/stop/name/topology/{topoName}/node/{nodeName}/token/{token}", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request     $request
     * @param string      $topoName
     * @param string      $nodeName
     * @param null|string $token
     *
     * @return Response
     */
    public function stopAction(Request $request, string $topoName, string $nodeName, ?string $token = NULL): Response
    {
        try {
            $res = $this->handler->run($topoName, $nodeName, $request->request->all() ?? '', $token, TRUE);

            return $this->getResponse($res, 200);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders([], $e));
        }
    }

    /**
     * @Route("/longRunning/{nodeId}/process", methods={"GET", "POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $nodeId
     *
     * @return Response
     */
    public function processAction(Request $request, string $nodeId): Response
    {
        try {
            $data = $this->handler->process($nodeId, (string) json_encode($request->getContent()), $request->headers->all());

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
        }
    }

    /**
     * @Route("/longRunning/{nodeId}/process/test", methods={"GET", "OPTIONS"})
     *
     * @param string $nodeId
     *
     * @return Response
     */
    public function test(string $nodeId): Response
    {
        try {
            $this->handler->test($nodeId);

            return $this->getResponse([]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
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
            return $this->getResponse($this->handler->getTasksById(new GridRequestDto($request->headers->all()), $topo));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
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
            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
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
            return $this->getResponse($this->handler->getTasksById(
                new GridRequestDto($request->headers->all()),
                $topo,
                $node
            ));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
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
            return $this->getResponse($this->handler->getTasks(
                new GridRequestDto($request->headers->all()),
                $topo,
                $node
            ));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
        }
    }

}