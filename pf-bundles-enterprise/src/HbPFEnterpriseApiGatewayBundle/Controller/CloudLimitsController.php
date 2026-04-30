<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudLimitsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class CloudLimitsController
 *
 * Serves the dashboard polling endpoints for cloud plan-limit usage and history.
 * Distinct from {@see CloudMetricsController} which is gated by the cloud
 * instance secret used by orchesty-cloud admin sync.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class CloudLimitsController extends AbstractController
{

    /**
     * CloudLimitsController constructor.
     *
     * @param CloudLimitsHandler $handler
     */
    public function __construct(private readonly CloudLimitsHandler $handler)
    {
    }

    /**
     * @return Response
     */
    #[Route('/orchesty/metrics/limits-usage', methods: ['GET'])]
    public function usageAction(): Response
    {
        try {
            return new JsonResponse($this->handler->getUsage());
        } catch (Throwable $t) {
            return new JsonResponse(['message' => $t->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/limits-history', methods: ['GET'])]
    public function historyAction(Request $request): Response
    {
        $from    = $request->query->getString('from', '');
        $to      = $request->query->getString('to', '');
        $buckets = $request->query->getInt('buckets', 50);

        if ($from === '' || $to === '') {
            return new JsonResponse(
                ['message' => 'Query parameters "from" and "to" are required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            return new JsonResponse($this->handler->getHistory($from, $to, $buckets));
        } catch (Throwable $t) {
            return new JsonResponse(['message' => $t->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
