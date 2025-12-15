<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class AuditEntityController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class AuditEntityController extends AbstractController
{

    /**
     * @return Response
     */
    #[Route('/audit/entities', methods: ['GET'])]
    public function getAllAction(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller\AuditEntityController::getAllAction',
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/audit/entities/{id}', methods: ['GET'], requirements: ['id' => '\w+'])]
    public function getOneAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller\AuditEntityController::getOneAction',
            ['id' => $id],
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/audit/entities', methods: ['POST'])]
    public function createAction(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller\AuditEntityController::createAction',
            ['request' => $request],
        );
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/audit/entities/{id}', requirements: ['id' => '\w+'], methods: ['PUT'])]
    public function updateAction(Request $request, string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller\AuditEntityController::updateAction',
            ['request' => $request, 'id' => $id],
        );
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/audit/entities/{id}', requirements: ['id' => '\w+'], methods: ['DELETE'])]
    public function deleteAction(string $id): Response
    {
        return $this->forward(
            'Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller\AuditEntityController::deleteAction',
            ['id' => $id],
        );
    }

}
