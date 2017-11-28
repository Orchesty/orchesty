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
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CategoryController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 *
 * @Route(service="hbpf.configurator.controller.category")
 *
 */
class CategoryController extends FOSRestController
{

    /**
     * @var CategoryHandler
     */
    private $categoryHandler;

    /**
     * @Route("/categories")
     * @Method({"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getCategoriesAction(): Response
    {
        $this->construct();
        $data = $this->categoryHandler->getCategories();

        return new JsonResponse($data, 200);
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
        $this->construct();
        $data = $this->categoryHandler->createCategory($request->request->all());

        return new JsonResponse($data, 200);
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->categoryHandler) {
            $this->categoryHandler = $this->container->get('hbpf.handler.category');
        }
    }

}