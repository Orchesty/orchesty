<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 2:00 PM
 */

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesFramework\HbPFConnectorBundle\Controller
 *
 * @Route(service="hbpf.controller.connector")
 */
class ConnectorController extends FOSRestController
{

    /**
     * @var ConnectorHandler
     */
    private $handler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @Route("/connector/{id}/webhook")
     * @Method({"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     */
    public function processEventAction(string $id, Request $request): Response
    {
        $this->construct();

        try {
            $data     = $this->handler->processEvent($id, $request);
            $response = new Response(
                $data->getData(),
                200,
                ControllerUtils::createHeaders($data->getHeaders()
                )
            );
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response = new Response(
                ControllerUtils::createExceptionData($e, TRUE),
                500,
                ControllerUtils::createHeaders($request->headers->all(), $e)
            );
        }

        return $response;
    }

    /**
     * @Route("/connector/{id}/webhook/test")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonResponse
     */
    public function processEventTestAction(Request $request, string $id): JsonResponse
    {
        $this->construct();

        try {
            $this->handler->processTest($id);
            $response = new JsonResponse('', 200);
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response = new JsonResponse(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders($request->headers->all(), $e)
            );
        }

        return $response;
    }

    /**
     * @Route("/connector/{id}/action")
     * @Method({"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function processActionAction(string $id, Request $request): JsonResponse
    {
        $this->construct();

        try {
            $data     = $this->handler->processAction($id, $request);
            $response = new JsonResponse(
                $data->getData(),
                200,
                ControllerUtils::createHeaders($data->getHeaders()),
                TRUE);
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response = new JsonResponse(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders($request->headers->all(), $e)
            );
        }

        return $response;
    }

    /**
     * @Route("/connector/{id}/action/test")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonResponse
     */
    public function processActionTestAction(Request $request, string $id): JsonResponse
    {
        $this->construct();

        try {
            $this->handler->processTest($id);
            $response = new JsonResponse('', 200);
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response = new JsonResponse(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders($request->headers->all(), $e)
            );
        }

        return $response;
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->handler) {
            $this->handler = $this->container->get('hbpf.handler.connector');
        }

        if (!$this->logger) {
            $this->logger = $this->container->get('monolog.logger.commons');
        }
    }

}