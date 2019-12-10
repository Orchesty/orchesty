<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SdkController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class SdkController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var SdkHandler
     */
    private $handler;

    /**
     * SdkController constructor.
     *
     * @param SdkHandler $handler
     */
    public function __construct(SdkHandler $handler)
    {
        $this->handler = $handler;
        $this->logger  = new NullLogger();
    }

    /**
     * @Route("/sdks", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getAllAction(): Response
    {
        return $this->getResponse($this->handler->getAll());
    }

    /**
     * @Route("/sdks/{id}", methods={"GET", "OPTIONS"}, requirements={"id": "\w+"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getOneAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->getOne($id));
        } catch (DocumentNotFoundException $e) {
            return $this->getErrorResponse($e, 404);
        }
    }

    /**
     * @Route("/sdks", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->create($request->request->all()));
        } catch (PipesFrameworkException | MongoDBException $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @Route("/sdks/{id}", methods={"PUT", "OPTIONS"}, requirements={"id": "\w+"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->handler->update($id, $request->request->all()));
        } catch (DocumentNotFoundException | MongoDBException $e) {
            return $this->getErrorResponse($e, 404);
        }
    }

    /**
     * @Route("/sdks/{id}", methods={"DELETE", "OPTIONS"}, requirements={"id": "\w+"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->delete($id));
        } catch (DocumentNotFoundException | MongoDBException $e) {
            return $this->getErrorResponse($e, 404);
        }
    }

}
