<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller
 */
final class ConnectorController implements LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * ConnectorController constructor.
     *
     * @param ConnectorHandler $connectorHandler
     */
    public function __construct(private ConnectorHandler $connectorHandler)
    {
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
            $dto = $this->connectorHandler->processAction($id, $request);

            return $this->getResponse($dto->getData(), 200, ControllerUtils::createHeaders($dto->getHeaders()));
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
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

            return $this->getResponse([]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
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
            return $this->getResponse($this->connectorHandler->getConnectors());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
