<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CategoryController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class CategoryController extends AbstractFOSRestController
{

    /**
     * @Route("/categories", methods={"GET", "OPTIONS"})
     * @param Request $request
     *
     * @return Response
     */
    public function getCategoriesAction(Request $request): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Category:getCategories', ['query' => $request->query]);
    }

    /**
     * @Route("/categories", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function createCategoryAction(): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Category:createCategory');
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
        return $this->forward('HbPFConfiguratorBundle:Category:updateCategory', ['id' => $id]);
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
        return $this->forward('HbPFConfiguratorBundle:Category:deleteCategory', ['id' => $id]);
    }

}
