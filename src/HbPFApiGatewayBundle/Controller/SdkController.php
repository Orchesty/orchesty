<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class SdkController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class SdkController extends AbstractController
{

    /**
     * @return Response
     */
    #[Route('/sdks', methods: ['GET'])]
    public function getAllAction(): Response
    {
        return $this->forward('Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getAllAction');
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/sdks/{id}', methods: ['GET'], requirements: ['id' => '\w+'])]
    public function getOneAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getOneAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/sdks', methods: ['POST'])]
    public function createAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::createAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/sdks/{id}', requirements: ['id' => '\w+'], methods: ['PUT'])]
    public function updateAction(Request $request, string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::updateAction',
            ['request' => $request, 'id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/sdks/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::deleteAction',
            ['id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/sdks/{id}/tunnel-env', requirements: ['id' => '\w+'], methods: ['GET'])]
    public function getTunnelEnvAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getTunnelEnvAction',
            ['id' => $id],
        );
    }

}
