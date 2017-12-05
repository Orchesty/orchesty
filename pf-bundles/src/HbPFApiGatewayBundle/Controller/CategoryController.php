<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CategoryController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.category")
 */
class CategoryController extends FOSRestController
{

    /**
     * @Route("/categories")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getCategoriesAction(Request $request): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Category:getCategories', ['query' => $request->query]);
    }

    /**
     * @Route("/categories")
     * @Method({"POST", "OPTIONS"})
     *
     * @return Response
     */
    public function createCategoryAction(): Response
    {
        return $this->forward('HbPFConfiguratorBundle:Category:createCategory');
    }

    /**
     * @Route("/categories/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"PUT", "PATCH", "OPTIONS"})
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
     * @Route("/categories/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"DELETE", "OPTIONS"})
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