<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Controller;

use Hanaboso\CommonsBundle\Exception\FileStorageException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class TableParserController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Controller
 */
class TableParserController
{

    use ControllerTrait;

    /**
     * @var TableParserHandler
     */
    private $tableParserHandler;

    /**
     * TableParserController constructor.
     *
     * @param TableParserHandler $tableParserHandler
     */
    public function __construct(TableParserHandler $tableParserHandler)
    {
        $this->tableParserHandler = $tableParserHandler;
    }

    /**
     * @Route("/parser/{type}/to/json", requirements={"type": "\w+"}, methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    public function toJsonAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseToJson($request->request->all()));
        } catch (TableParserHandlerException | FileStorageException $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/parser/{type}/to/json/test", requirements={"type": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function toJsonTestAction(): Response
    {
        try {
            $this->tableParserHandler->parseToJsonTest();

            return $this->getResponse('');
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/parser/json/to/{type}", requirements={"type": "\w+"}, methods={"POST"})
     *
     * @param Request $request
     * @param string  $type
     *
     * @return Response
     */
    public function fromJsonAction(Request $request, string $type): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseFromJson($type, $request->request->all()));
        } catch (TableParserHandlerException | TableParserException | FileStorageException | Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/parser/json/to/{type}/test", requirements={"type": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $type
     *
     * @return Response
     */
    public function fromJsonTestAction(string $type): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseFromJsonTest($type));
        } catch (TableParserException $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
        }
    }

}
