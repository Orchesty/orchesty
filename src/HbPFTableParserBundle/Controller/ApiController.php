<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Controller
 */
class ApiController extends FOSRestController
{

    /**
     * @var TableParserHandler
     *
     * @Autowire()
     */
    private $tableParserHandler;

    /**
     * @Route("/api/parser/{type}/to/json", requirements={"type"="\w+"})
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function toJsonAction(Request $request): Response
    {
        $response = new JsonResponse();
        try {
            $data = $this->tableParserHandler->parseToJson($request->request->all());
            $response->setStatusCode(200);
        } catch (TableParserHandlerException $e) {
            $data = ControllerUtils::createExceptionData($e);
            $response->setStatusCode(500);
        }

        return $response->setData($data);
    }

    /**
     * @Route("/api/parser/{type}/to/json/test", requirements={"type"="\w+"})
     * @Method("POST")
     *
     * @return Response
     *
     */
    public function toJsonTestAction(): Response
    {
        $response = new JsonResponse();
        $data = '';
        try {
            $this->tableParserHandler->parseToJsonTest();
            $response->setStatusCode(200);
        } catch (TableParserException $e) {
            $data = ControllerUtils::createExceptionData($e);
            $response->setStatusCode(500);
        }

        return $response->setData($data);
    }

    /**
     * @Route("/api/parser/json/to/{type}, requirements={"type"="\w+"})
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $type
     *
     * @return Response
     */
    public function fromJsonAction(Request $request, string $type): Response
    {
        $response = new JsonResponse();
        try {
            $data = $this->tableParserHandler->parseFromJson($type, $request->request->all());
            $response->setStatusCode(200);
        } catch (TableParserException $e) {
            $data = ControllerUtils::createExceptionData($e);
            $response->setStatusCode(500);
        }

        return $response->setData($data);
    }

    /**
     * @Route("/api/parser/json/to/{type}/test, requirements={"type"="\w+"})
     * @Method("POST")
     *
     * @param string $type
     *
     * @return Response
     */
    public function fromJsonTestAction(string $type): Response
    {
        $response = new JsonResponse();
        $data     = '';
        try {
            $this->tableParserHandler->parseFromJsonTest($type);
            $response->setStatusCode(200);
        } catch (TableParserException $e) {
            $data = ControllerUtils::createExceptionData($e);
            $response->setStatusCode(500);
        }

        return $response->setData($data);
    }

}