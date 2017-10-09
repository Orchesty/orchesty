<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 2:00 PM
 */

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
     * @Route("/connector/{id}/webhook", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function processEventAction(string $id, Request $request): JsonResponse
    {
        $this->construct();

        try {
            $data     = $this->handler->processEvent($id, $request);
            $response = new JsonResponse(
                $data->getData(),
                200,
                ControllerUtils::createHeaders($data->getHeaders()
                ),
                TRUE);
        } catch (ConnectorException $e) {
            $response = new JsonResponse(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders([], $e)
            );
        }

        return $response;
    }

    /**
     * @Route("/connector/{id}/webhook/test", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function processEventTestAction(string $id): JsonResponse
    {
        $this->construct();

        try {
            $this->handler->processEventTest($id);
            $response = new JsonResponse('', 200);
        } catch (ConnectorException $e) {
            $response = new JsonResponse(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders([], $e)
            );
        }

        return $response;
    }

    /**
     * @Route("/connector/{id}/action", defaults={}, requirements={"id": "[\w-]+"})
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
                ControllerUtils::createHeaders($data->getHeaders()
                ),
                TRUE);
        } catch (ConnectorException $e) {
            $response = new JsonResponse(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders([], $e)
            );
        }

        return $response;
    }

    /**
     * @Route("/connector/{id}/action/test", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function processActionTestAction(string $id): JsonResponse
    {
        $this->construct();

        try {
            $this->handler->processActionTest($id);
            $response = new JsonResponse('', 200);
        } catch (ConnectorException $e) {
            $response = new JsonResponse(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders([], $e)
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
    }

}