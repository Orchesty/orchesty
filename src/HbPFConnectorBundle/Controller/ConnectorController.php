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
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesFramework\HbPFConnectorBundle\Controller
 * @todo    remove logger
 */
class ConnectorController extends FOSRestController implements LoggerAwareInterface
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
        try {
            $data = $this->connectorHandler->processEvent($id, $request);

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
        try {
            $this->connectorHandler->processTest($id);

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
        try {
            $data = $this->connectorHandler->processAction($id, $request);

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
        try {
            $this->connectorHandler->processTest($id);

            return $this->getResponse('');
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders($request->headers->all(), $e));
        }
    }

    /**
     * @required
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}