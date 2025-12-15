<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class CategoryController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class CategoryController extends AbstractController
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/categories', methods: ['GET'])]
    public function getCategoriesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::getCategoriesAction',
            ['query' => $request->query],
        );
    }

    /**
     * @return Response
     */
    #[Route('/categories', methods: ['POST'])]
    public function createCategoryAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::createCategoryAction',
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/categories/{id}', requirements: ['id' => '\w+'], methods: ['PUT', 'PATCH'])]
    public function updateCategoryAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::updateCategoryAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/categories/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteCategoryAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::deleteCategoryAction',
            ['id' => $id],
        );
    }

}
