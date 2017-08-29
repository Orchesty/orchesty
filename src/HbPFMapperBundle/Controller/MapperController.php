<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMapperBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesFramework\HbPFMapperBundle\Handler\MapperHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MapperController
 *
 * @package Hanaboso\PipesFramework\HbPFMapperBundle\Controller
 *
 * @Route(service="hbpf.mapper.controller.mapper")
 */
class MapperController extends FOSRestController
{

    /**
     * @var MapperHandler
     */
    private $mapperHandler;

    /**
     * MapperController constructor.
     *
     * @param MapperHandler $mapperHandler
     */
    public function __construct(MapperHandler $mapperHandler)
    {
        $this->mapperHandler = $mapperHandler;
    }

    /**
     * @Route("/api/mapper/{id}/process", requirements={"id": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonResponse
     */
    public function processAction(Request $request, string $id): JsonResponse
    {
        try {
            $data     = $this->mapperHandler->process($id, $request->request->all());
            $response = new JsonResponse($data, 200);
        } catch (MapperException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/api/mapper/{id}/process/test", requirements={"id": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return JsonResponse
     */
    public function processTestAction(Request $request, string $id): JsonResponse
    {
        try {
            $this->mapperHandler->processTest($id, $request->request->all());
            $response = new JsonResponse([], 200);
        } catch (MapperException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

}