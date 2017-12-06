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
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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

    use ControllerTrait;

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
            $data = $this->handler->processEvent($id, $request);

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @Route("/connector/{id}/webhook/test")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processEventTestAction(Request $request, string $id): Response
    {
        $this->construct();

        try {
            $this->handler->processTest($id);

            return $this->getResponse('');
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @Route("/connector/{id}/action")
     * @Method({"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     */
    public function processActionAction(string $id, Request $request): Response
    {
        $this->construct();

        try {
            $data = $this->handler->processAction($id, $request);

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @Route("/connector/{id}/action/test")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processActionTestAction(Request $request, string $id): Response
    {
        $this->construct();

        try {
            $this->handler->processTest($id);

            return $this->getResponse('');
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
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