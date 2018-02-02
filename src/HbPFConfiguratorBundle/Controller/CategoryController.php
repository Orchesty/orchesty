<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/27/17
 * Time: 3:18 PM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CategoryController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
class CategoryController extends FOSRestController
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
    }

    /**
     * @Route("/categories")
     * @Method({"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getCategoriesAction(): Response
    {
        $data = $this->categoryHandler->getCategories();

        return $this->getResponse($data);
    }

    /**
     * @Route("/categories")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createCategoryAction(Request $request): Response
    {
        $data = $this->categoryHandler->createCategory($request->request->all());

        return $this->getResponse($data);
    }

    /**
     * @Route("/categories/{id}", defaults={}, requirements={"id": "\w+"})
     * @Method({"PUT", "PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateCategoryAction(Request $request, string $id): Response
    {
        $data = $this->categoryHandler->updateCategory($id, $request->request->all());

        return $this->getResponse($data);
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
        $data = $this->categoryHandler->deleteCategory($id);

        return $this->getResponse($data);
    }

}