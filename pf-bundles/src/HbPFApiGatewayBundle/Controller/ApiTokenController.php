<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ApiTokenController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class ApiTokenController extends AbstractController
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/apiTokens', methods: ['GET'])]
    public function getApiTokensAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\ApiTokenController::getApiTokensAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/apiTokens', methods: ['POST'])]
    public function createAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\ApiTokenController::createAction',
            ['request' => $request],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/apiTokens/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\ApiTokenController::deleteAction',
            ['id' => $id],
        );
    }

}
