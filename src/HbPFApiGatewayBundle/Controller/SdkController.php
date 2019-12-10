<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SdkController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class SdkController extends AbstractFOSRestController
{

    /**
     * @Route("/sdks", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getAllAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getAllAction');
    }

    /**
     * @Route("/sdks/{id}", methods={"GET", "OPTIONS"}, requirements={"id": "\w+"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getOneAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getOneAction',
            ['id' => $id]
        );
    }

    /**
     * @Route("/sdks", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::createAction',
            ['request' => $request]
        );
    }

    /**
     * @Route("/sdks/{id}", methods={"PUT", "OPTIONS"}, requirements={"id": "\w+"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateAction(Request $request, string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::updateAction',
            ['request' => $request, 'id' => $id]
        );
    }

    /**
     * @Route("/sdks/{id}", methods={"DELETE", "OPTIONS"}, requirements={"id": "\w+"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::deleteAction',
            ['id' => $id]
        );
    }

}
