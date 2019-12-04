<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class CategoryController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
class CategoryController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var CategoryHandler
     */
    private $categoryHandler;

    /**
     * CategoryController constructor.
     *
     * @param CategoryHandler $categoryHandler
     */
    public function __construct(CategoryHandler $categoryHandler)
    {
        $this->categoryHandler = $categoryHandler;
        $this->logger          = new NullLogger();
    }

    /**
     * @Route("/categories", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getCategoriesAction(): Response
    {
        $data = $this->categoryHandler->getCategories();

        return $this->getResponse($data);
    }

    /**
     * @Route("/categories", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createCategoryAction(Request $request): Response
    {
        try {
            $data = $this->categoryHandler->createCategory($request->request->all());

            return $this->getResponse($data);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/categories/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PUT", "PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateCategoryAction(Request $request, string $id): Response
    {
        try {
            $data = $this->categoryHandler->updateCategory($id, $request->request->all());

            return $this->getResponse($data);
        } catch (CategoryException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/categories/{id}", defaults={}, requirements={"id": "\w+"}, methods={"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteCategoryAction(string $id): Response
    {
        try {
            $data = $this->categoryHandler->deleteCategory($id);

            return $this->getResponse($data);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
