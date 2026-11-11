<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class StatusController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class StatusController extends AbstractController
{

    /**
     * StatusController constructor.
     *
     * @param string  $orchestyCloudUrl
     * @param string  $orchestyCloudFrontendUrl
     * @param bool    $featureEnterpriseDashboards
     * @param bool    $featureTraceAuditing
     * @param bool    $featureAuditLogs
     * @param bool    $featurePulse
     * @param mixed[] $systemWorkerNames
     * @param int     $limitTopologySlots
     * @param int     $limitMessages
     * @param int     $limitStorageGb
     * @param int     $limitTrashDuplication
     */
    public function __construct(
        private readonly string $orchestyCloudUrl = '',
        private readonly string $orchestyCloudFrontendUrl = '',
        private readonly bool $featureEnterpriseDashboards = FALSE,
        private readonly bool $featureTraceAuditing = FALSE,
        private readonly bool $featureAuditLogs = FALSE,
        private readonly bool $featurePulse = FALSE,
        private readonly array $systemWorkerNames = [],
        private readonly int $limitTopologySlots = 0,
        private readonly int $limitMessages = 0,
        private readonly int $limitStorageGb = 0,
        private readonly int $limitTrashDuplication = 0,
    )
    {
    }

    /**
     * @return Response
     */
    #[Route('/status', methods: ['GET'])]
    public function getStatusAction(): Response
    {
        $isCloud = $this->orchestyCloudUrl !== '';

        // Feature flags are honoured exactly as injected, in BOTH cloud and
        // on-prem. The two on-prem editions resolve them differently:
        //   - Community (core) registers this controller with no arguments, so
        //     the constructor defaults (FALSE) apply — it has none of these
        //     features.
        //   - Enterprise binds them to %feature_*% (env, default TRUE) so an
        //     Enterprise instance has every feature unless an operator explicitly
        //     switches one off via ORCHESTY_FEATURE_* (e.g. PULSE=false).
        $data = [
            'cloudMode' => $isCloud,
            'features'  => [
                'auditLogs'            => $this->featureAuditLogs,
                'enterpriseDashboards' => $this->featureEnterpriseDashboards,
                'pulse'                => $this->featurePulse,
                'traceAuditing'        => $this->featureTraceAuditing,
            ],
            'status'    => 'ok',
        ];

        if ($isCloud) {
            $frontendUrl      = $this->orchestyCloudFrontendUrl !== ''
                ? $this->orchestyCloudFrontendUrl
                : $this->orchestyCloudUrl;
            $data['cloudUrl'] = rtrim($frontendUrl, '/');
            $data['limits']   = [
                'messages'               => $this->limitMessages,
                'storageGb'              => $this->limitStorageGb,
                'topologySlots'          => $this->limitTopologySlots,
                'trashDuplicationLimit'  => $this->limitTrashDuplication,
            ];
        }

        if ($this->systemWorkerNames !== []) {
            $data['systemWorkerNames'] = $this->systemWorkerNames;
        }

        return new JsonResponse($data);
    }

}
