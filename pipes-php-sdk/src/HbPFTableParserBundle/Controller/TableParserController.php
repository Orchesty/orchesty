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
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/parser/{type}/to/json', methods: ['POST'], requirements: ['type' => '\w+'])]
    public function toJsonAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseToJson($request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @return Response
     */
    #[Route('/parser/{type}/to/json/test', methods: ['GET', 'OPTIONS'], requirements: ['type' => '\w+'])]
    public function toJsonTestAction(): Response
    {
        try {
            $test = $this->tableParserHandler->parseToJsonTest();

            return $this->getResponse(['test' => $test]);
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
    #[Route('/parser/json/to/{type}', methods: ['POST'], requirements: ['type' => '\w+'])]
    public function fromJsonAction(Request $request, string $type): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseFromJson($type, $request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @param string $type
     *
     * @return Response
     */
    #[Route('/parser/json/to/{type}/test', methods: ['GET', 'OPTIONS'], requirements: ['type' => '\w+'])]
    public function fromJsonTestAction(string $type): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseFromJsonTest($type));
        } catch (TableParserException $e) {
            return $this->getErrorResponse($e);
        }
    }

}
