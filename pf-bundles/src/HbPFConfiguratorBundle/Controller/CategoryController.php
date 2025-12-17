<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
     * @return Response
     */
    #[Route('/categories', methods: ['GET'])]
    public function getCategoriesAction(): Response
    {
        return $this->getResponse($this->categoryHandler->getCategories());
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/categories', methods: ['POST'])]
    public function createCategoryAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->categoryHandler->createCategory($request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/categories/{id}', requirements: ['id' => '\w+'], methods: ['PUT', 'PATCH'])]
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
     * @param string $id
     *
     * @return Response
     */
    #[Route('/categories/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteCategoryAction(string $id): Response
    {
        try {
            return $this->getResponse($this->categoryHandler->deleteCategory($id));
        } catch (CategoryException $e) {
            return match ($e->getCode()) {
                CategoryException::CATEGORY_NOT_FOUND => $this->getErrorResponse($e, 404),
                default => $this->getErrorResponse($e, 400),
            };
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
