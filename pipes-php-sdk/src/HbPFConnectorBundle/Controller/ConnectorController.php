<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller
 * @todo    remove logger
 */
class ConnectorController extends AbstractFOSRestController implements LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var ConnectorHandler
     */
    private $connectorHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ConnectorController constructor.
     *
     * @param ConnectorHandler $connectorHandler
     */
    public function __construct(ConnectorHandler $connectorHandler)
    {
        $this->connectorHandler = $connectorHandler;
        $this->logger           = new NullLogger();
    }

    /**
     * @Route("/connector/{id}/webhook", methods={"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     */
    public function processEventAction(string $id, Request $request): Response
    {
        try {
            $data = $this->connectorHandler->processEvent($id, $request);

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @Route("/connector/{id}/webhook/test", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processEventTestAction(Request $request, string $id): Response
    {
        try {
            $this->connectorHandler->processTest($id);

            return $this->getResponse('');
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @Route("/connector/{id}/action", methods={"POST", "OPTIONS"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    public function processActionAction(string $id, Request $request): Response
    {
        try {
            $data = $this->connectorHandler->processAction($id, $request);

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @Route("/connector/{id}/action/test", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processActionTestAction(Request $request, string $id): Response
    {
        try {
            $this->connectorHandler->processTest($id);

            return $this->getResponse('');
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @Route("/connector/list", methods={"GET"})
     *
     * @return Response
     */
    public function listOfConnectorsAction(): Response
    {
        try {
            $data = $this->connectorHandler->getConnectors();

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500);
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
