<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMapperBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesFramework\HbPFMapperBundle\Handler\MapperHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @return Response
     */
    public function processAction(Request $request, string $id): Response
    {
        try {
            $data = $this->mapperHandler->process($id, $request->request->all());
            $view = $this->view($data, 200);
        } catch (MapperException $e) {
            $view = $this->view(ControllerUtils::createExceptionData($e), 500);
        }

        return $this->handleView($view);
    }

    /**
     * @Route("/api/mapper/{id}/process/test", requirements={"id": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processTestAction(Request $request, string $id): Response
    {
        try {
            $data = $this->mapperHandler->processTest($id, $request->request->all());
            $view = $this->view($data, 200);
        } catch (MapperException $e) {
            $view = $this->view(ControllerUtils::createExceptionData($e), 500);
        }

        return $this->handleView($view);
    }

}