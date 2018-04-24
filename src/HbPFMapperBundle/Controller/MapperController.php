<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMapperBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesFramework\HbPFMapperBundle\Handler\MapperHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MapperController
 *
 * @package Hanaboso\PipesFramework\HbPFMapperBundle\Controller
 */
class MapperController extends FOSRestController
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
     * @Route("/mapper/{id}/process", requirements={"id": "\w+"})
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

            return $this->getResponse($data);
        } catch (MapperException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/mapper/{id}/process/test", requirements={"id": "\w+"})
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
            $this->mapperHandler->processTest($id, $request->request->all());

            return $this->getResponse([]);
        } catch (MapperException $e) {
            return $this->getErrorResponse($e);
        }
    }

}