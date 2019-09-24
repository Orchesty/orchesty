<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class MapperController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller
 */
class MapperController extends AbstractFOSRestController
{

    use ControllerTrait;

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
     * @Route("/mapper/{id}/process", requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    public function processAction(Request $request, string $id): Response
    {
        try {
            $data = $this->mapperHandler->process($id, $request->request->all());

            return $this->getResponse($data);
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        }
    }

    /**
     * @Route("/mapper/{id}/process/test", requirements={"id": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function processTestAction(Request $request, string $id): Response
    {
        try {
            $this->mapperHandler->processTest($id, $request->request->all());

            return $this->getResponse([]);
        } catch (MapperException $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/mapper/list", methods={"GET"})
     *
     * @return Response
     */
    public function listOfMappersAction(): Response
    {
        try {
            $data = $this->mapperHandler->getMappers();

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {

            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
        }
    }

}
