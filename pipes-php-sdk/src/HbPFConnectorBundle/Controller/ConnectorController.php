<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoControllerTrait;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\Utils\System\ControllerUtils;
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

    use ProcessDtoControllerTrait;

    /**
     * ConnectorController constructor.
     *
     * @param ConnectorHandler $connectorHandler
     */
    public function __construct(private ConnectorHandler $connectorHandler)
    {
    }

    /**
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    #[Route('/connector/{id}/action', methods: ['POST', 'OPTIONS'])]
    public function processActionAction(string $id, Request $request): Response
    {
        try {
            $dto = $this->connectorHandler->processAction($id, $request);

            return $this->getResponseFromDto($dto);
        } catch (OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponseFromDto(ProcessDtoFactory::createFromRequest($request), $e);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/connector/{id}/action/test', methods: ['GET', 'OPTIONS'])]
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
     * @return Response
     */
    #[Route('/connector/list', methods: ['GET'])]
    public function listOfConnectorsAction(): Response
    {
        try {
            return $this->getResponse($this->connectorHandler->getConnectors());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
