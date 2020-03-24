<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CategoryController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class CategoryController extends AbstractController
{

    /**
     * @Route("/categories", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getCategoriesAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::getCategoriesAction',
            ['query' => $request->query]
        );
    }

    /**
     * @Route("/categories", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function createCategoryAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::createCategoryAction'
        );
    }

    /**
     * @Route("/categories/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PUT", "PATCH", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function updateCategoryAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::updateCategoryAction',
            ['id' => $id]
        );
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
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::deleteCategoryAction',
            ['id' => $id]
        );
    }

}
