<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Controller
 */
class TableParserController extends FOSRestController
{

    use ControllerTrait;

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
     * @return Response
     *
     */
    public function toJsonAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseToJson($request->request->all()));
        } catch (TableParserHandlerException | FileStorageException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/parser/{type}/to/json/test", requirements={"type": "\w+"})
     * @Method("POST")
     *
     * @return Response
     */
    public function toJsonTestAction(): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseToJsonTest());
        } catch (TableParserHandlerException | FileStorageException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/parser/json/to/{type}", requirements={"type": "\w+"})
     * @Method("POST")
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
        } catch (TableParserHandlerException | TableParserException | FileStorageException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/parser/json/to/{type}/test", requirements={"type": "\w+"})
     * @Method("POST")
     *
     * @param string $type
     *
     * @return Response
     */
    public function fromJsonTestAction(string $type): Response
    {
        try {
            return $this->getResponse($this->tableParserHandler->parseFromJsonTest($type));
        } catch (TableParserHandlerException | TableParserException | FileStorageException $e) {
            return $this->getErrorResponse($e);
        }
    }

}