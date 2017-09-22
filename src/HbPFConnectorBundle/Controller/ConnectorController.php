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
     * ConnectorController constructor.
     *
     * @param ConnectorHandler $handler
     */
    function __construct(ConnectorHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/api/connector/{id}/webhook", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function processEvent(string $id, Request $request): JsonResponse
    {
        try {
            $data     = $this->handler->processEvent($id, $request);
            $response = new JsonResponse($data->getData(), 200, $data->getHeaders(), TRUE);
        } catch (ConnectorException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/api/connector/{id}/action", defaults={}, requirements={"id": "[\w-]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function processAction(string $id, Request $request): JsonResponse
    {
        try {
            $data     = $this->handler->processAction($id, $request);
            $response = new JsonResponse($data->getData(), 200, $data->getHeaders(), TRUE);
        } catch (ConnectorException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

}