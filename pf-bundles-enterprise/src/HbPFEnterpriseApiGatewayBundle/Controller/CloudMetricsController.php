<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudLimitsHandler;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudMetricsHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class CloudMetricsController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class CloudMetricsController
{

    use ControllerTrait;

    /**
     * CloudMetricsController constructor.
     *
     * @param CloudMetricsHandler $handler
     * @param CloudLimitsHandler  $limitsHandler
     * @param string              $instanceId
     * @param string              $instanceSecret
     */
    public function __construct(
        private readonly CloudMetricsHandler $handler,
        private readonly CloudLimitsHandler $limitsHandler,
        private readonly string $instanceId,
        private readonly string $instanceSecret,
    )
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/published-topologies', methods: ['GET'])]
    public function publishedTopologiesAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->getResponse($this->handler->getPublishedTopologiesCount());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/limiter-count', methods: ['GET'])]
    public function limiterCountAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->getResponse($this->handler->getLimiterCount());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/limiter-history', methods: ['GET'])]
    public function limiterHistoryAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

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
            return $this->getResponse($this->handler->getLimiterHistory($from, $to, $buckets));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/limits-usage-split', methods: ['GET'])]
    public function limitsUsageSplitAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->getResponse($this->limitsHandler->getUsageSplit());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/limits-history-split', methods: ['GET'])]
    public function limitsHistorySplitAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $from    = $request->query->getString('from', '');
        $to      = $request->query->getString('to', '');
        $buckets = $request->query->getInt('buckets', 60);

        if ($from === '' || $to === '') {
            return new JsonResponse(
                ['message' => 'Query parameters "from" and "to" are required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            return $this->getResponse($this->limitsHandler->getHistorySplit($from, $to, $buckets));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/resources-history', methods: ['GET'])]
    public function resourcesHistoryAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $from    = $request->query->getString('from', '');
        $to      = $request->query->getString('to', '');
        $buckets = $request->query->getInt('buckets', 60);

        if ($from === '' || $to === '') {
            return new JsonResponse(
                ['message' => 'Query parameters "from" and "to" are required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            return $this->getResponse($this->handler->getResourcesHistory($from, $to, $buckets));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/queue-history', methods: ['GET'])]
    public function queueHistoryAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $from    = $request->query->getString('from', '');
        $to      = $request->query->getString('to', '');
        $buckets = $request->query->getInt('buckets', 60);

        if ($from === '' || $to === '') {
            return new JsonResponse(
                ['message' => 'Query parameters "from" and "to" are required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            return $this->getResponse($this->handler->getQueueHistory($from, $to, $buckets));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/log-retention-latest', methods: ['GET'])]
    public function logRetentionLatestAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->getResponse($this->handler->getLogRetentionLatest());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/metrics/log-retention-history', methods: ['GET'])]
    public function logRetentionHistoryAction(Request $request): Response
    {
        if (!$this->validateCloudAuth($request)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $from    = $request->query->getString('from', '');
        $to      = $request->query->getString('to', '');
        $buckets = $request->query->getInt('buckets', 60);

        if ($from === '' || $to === '') {
            return new JsonResponse(
                ['message' => 'Query parameters "from" and "to" are required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            return $this->getResponse($this->handler->getLogRetentionHistory($from, $to, $buckets));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function validateCloudAuth(Request $request): bool
    {
        if ($this->instanceId === '' || $this->instanceSecret === '') {
            return FALSE;
        }

        $id     = $request->query->getString('instanceId', '');
        $secret = $request->query->getString('instanceSecret', '');

        return $id === $this->instanceId && hash_equals($this->instanceSecret, $secret);
    }

}
