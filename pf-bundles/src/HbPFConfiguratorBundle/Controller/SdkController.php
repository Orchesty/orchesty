<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class SdkController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class SdkController extends AbstractController
{

    use ControllerTrait;

    /**
     * SdkController constructor.
     *
     * @param SdkHandler $handler
     */
    public function __construct(private SdkHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/sdks', methods: ['GET'])]
    public function getAllAction(): Response
    {
        return $this->getResponse($this->handler->getAll());
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/sdks/{id}', requirements: ['id' => '\w+'], methods: ['GET'])]
    public function getOneAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->getOne($id));
        } catch (DocumentNotFoundException $e) {
            return $this->getErrorResponse($e, 404);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/sdks', methods: ['POST'])]
    public function createAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->create($request->request->all()));
        } catch (PipesFrameworkException | MongoDBException $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/sdks/{id}', requirements: ['id' => '\w+'], methods: ['PUT'])]
    public function updateAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->handler->update($id, $request->request->all()));
        } catch (DocumentNotFoundException | MongoDBException $e) {
            return $this->getErrorResponse($e, 404);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/sdks/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->delete($id));
        } catch (DocumentNotFoundException | MongoDBException $e) {
            return $this->getErrorResponse($e, 404);
        }
    }

}
