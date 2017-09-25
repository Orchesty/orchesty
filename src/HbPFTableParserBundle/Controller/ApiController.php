<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Controller
 *
 * @Route(service="hbpf.parser.table.controller.api")
 */
class ApiController extends FOSRestController
{

    /**
     * @var TableParserHandler
     */
    private $tableParserHandler;

    /**
     * ApiController constructor.
     *
     * @param TableParserHandler $tableParserHandler
     */
    public function __construct(TableParserHandler $tableParserHandler)
    {
        $this->tableParserHandler = $tableParserHandler;
    }

    /**
     * @Route("/parser/{type}/to/json", requirements={"type": "\w+"})
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     */
    public function toJsonAction(Request $request): JsonResponse
    {
        try {
            return new JsonResponse($this->tableParserHandler->parseToJson($request->request->all()));
        } catch (TableParserHandlerException | FileStorageException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/parser/{type}/to/json/test", requirements={"type": "\w+"})
     * @Method("POST")
     *
     * @return JsonResponse
     *
     */
    public function toJsonTestAction(): JsonResponse
    {
        try {
            return new JsonResponse($this->tableParserHandler->parseToJsonTest());
        } catch (TableParserHandlerException | FileStorageException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/parser/json/to/{type}", requirements={"type": "\w+"})
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $type
     *
     * @return JsonResponse
     */
    public function fromJsonAction(Request $request, string $type): JsonResponse
    {
        try {
            return new JsonResponse($this->tableParserHandler->parseFromJson($type, $request->request->all()));
        } catch (TableParserHandlerException | TableParserException | FileStorageException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/parser/json/to/{type}/test", requirements={"type": "\w+"})
     * @Method("POST")
     *
     * @param string $type
     *
     * @return JsonResponse
     */
    public function fromJsonTestAction(string $type): JsonResponse
    {
        try {
            return new JsonResponse($this->tableParserHandler->parseFromJsonTest($type));
        } catch (TableParserHandlerException | TableParserException | FileStorageException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

}