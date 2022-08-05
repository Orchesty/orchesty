<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Handler\BatchHandler;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class BatchController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFBatchBundle\Controller
 */
final class BatchController implements LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * BatchController constructor.
     *
     * @param BatchHandler $batchHandler
     */
    public function __construct(private BatchHandler $batchHandler)
    {
    }

    /**
     * @Route("/batch/{id}/action", methods={"POST", "OPTIONS"})
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
            $dto = $this->batchHandler->processAction($id, $request);

            return $this->getResponse($dto->getBridgeData(), 200, ControllerUtils::createHeaders($dto->getHeaders()));
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/batch/{id}/action/test", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processActionTestAction(Request $request, string $id): Response
    {
        try {
            $this->batchHandler->processTest($id);

            return $this->getResponse([]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/batch/list", methods={"GET"})
     *
     * @return Response
     */
    public function listOfConnectorsAction(): Response
    {
        try {
            return $this->getResponse($this->batchHandler->getBeaches());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
