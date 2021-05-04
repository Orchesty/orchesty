<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
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
final class CategoryController
{

    use ControllerTrait;

    /**
     * CategoryController constructor.
     *
     * @param CategoryHandler $categoryHandler
     */
    public function __construct(private CategoryHandler $categoryHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @Route("/categories", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getCategoriesAction(): Response
    {
        return $this->getResponse($this->categoryHandler->getCategories());
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
            return $this->getResponse($this->categoryHandler->createCategory($request->request->all()));
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
            return $this->getResponse($this->categoryHandler->updateCategory($id, $request->request->all()));
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
            return $this->getResponse($this->categoryHandler->deleteCategory($id));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
