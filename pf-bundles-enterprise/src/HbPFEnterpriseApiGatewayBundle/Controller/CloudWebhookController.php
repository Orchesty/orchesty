<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudUserSyncHandler;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class CloudWebhookController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class CloudWebhookController extends AbstractController
{

    /**
     * CloudWebhookController constructor.
     *
     * @param CloudUserSyncHandler $cloudUserSyncHandler
     */
    public function __construct(private readonly CloudUserSyncHandler $cloudUserSyncHandler)
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/webhook/users', methods: ['POST'])]
    public function syncUsersAction(Request $request): Response
    {
        try {
            $data   = Json::decode($request->getContent());
            $result = $this->cloudUserSyncHandler->syncUsers($data);

            return new JsonResponse($result);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

}
