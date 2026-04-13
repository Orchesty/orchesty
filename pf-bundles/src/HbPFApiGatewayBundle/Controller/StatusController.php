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

        $data = [
            'cloudMode' => $isCloud,
            'features'  => [
                'auditLogs'            => $isCloud ? $this->featureAuditLogs : TRUE,
                'enterpriseDashboards' => $isCloud ? $this->featureEnterpriseDashboards : TRUE,
                'pulse'                => $isCloud ? $this->featurePulse : TRUE,
                'traceAuditing'        => $isCloud ? $this->featureTraceAuditing : TRUE,
            ],
            'status'    => 'ok',
        ];

        if ($isCloud) {
            $frontendUrl      = $this->orchestyCloudFrontendUrl !== ''
                ? $this->orchestyCloudFrontendUrl
                : $this->orchestyCloudUrl;
            $data['cloudUrl'] = rtrim($frontendUrl, '/');
            $data['limits']   = [
                'messages'      => $this->limitMessages,
                'storageGb'     => $this->limitStorageGb,
                'topologySlots' => $this->limitTopologySlots,
            ];
        }

        if ($this->systemWorkerNames !== []) {
            $data['systemWorkerNames'] = $this->systemWorkerNames;
        }

        return new JsonResponse($data);
    }

}
