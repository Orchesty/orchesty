<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Controller;

use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
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
final class TableParserController
{

    use ControllerTrait;

    /**
     * TableParserController constructor.
     *
     * @param TableParserHandler $tableParserHandler
     */
    public function __construct(private TableParserHandler $tableParserHandler)
    {
    }

    /**
     * @Route("/parser/{type}/to/json", requirements={"type": "\w+"}, methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function toJsonAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseToJson($request->request->all()));
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

            return $this->getResponse([]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
        } catch (Throwable $e) {
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
            return $this->getErrorResponse($e);
        }
    }

}
