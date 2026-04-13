<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\InAppNotificationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class InAppNotificationController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class InAppNotificationController extends AbstractController
{

    /**
     * InAppNotificationController constructor.
     *
     * @param InAppNotificationHandler $handler
     */
    public function __construct(private readonly InAppNotificationHandler $handler)
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/notifications/in-app', methods: ['GET'])]
    public function listAction(Request $request): Response
    {
        try {
            $page     = max(1, $request->query->getInt('page', 1));
            $limit    = min(100, max(1, $request->query->getInt('limit', 20)));
            $severity = $request->query->getString('severity', '');
            $from     = $request->query->getString('from', '');
            $to       = $request->query->getString('to', '');

            $result = $this->handler->list(
                [
                    'from'     => $from ?: NULL,
                    'severity' => $severity ?: NULL,
                    'to'       => $to ?: NULL,
                ],
                $page,
                $limit,
            );

            return new JsonResponse($result);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/notifications/in-app/count', methods: ['GET'])]
    public function countAction(Request $request): Response
    {
        try {
            $since = $request->query->getString('since', '');

            $count = $this->handler->countSince($since ?: NULL);

            return new JsonResponse(['count' => $count]);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

}
