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
     * @param string $orchesryCloudUrl
     * @param string $orchesryCloudFrontendUrl
     * @param string $orchesryCloudInstanceName
     * @param bool   $featureEnterpriseDashboards
     * @param bool   $featureTraceAuditing
     * @param bool   $featureAuditLogs
     * @param bool   $featurePulse
     */
    public function __construct(
        private readonly string $orchesryCloudUrl = '',
        private readonly string $orchesryCloudFrontendUrl = '',
        private readonly string $orchesryCloudInstanceName = '',
        private readonly bool $featureEnterpriseDashboards = FALSE,
        private readonly bool $featureTraceAuditing = FALSE,
        private readonly bool $featureAuditLogs = FALSE,
        private readonly bool $featurePulse = FALSE,
    )
    {
    }

    /**
     * @return Response
     */
    #[Route('/status', methods: ['GET'])]
    public function getStatusAction(): Response
    {
        $isCloud = $this->orchesryCloudUrl !== '';

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
            $frontendUrl          = $this->orchesryCloudFrontendUrl !== ''
                ? $this->orchesryCloudFrontendUrl
                : $this->orchesryCloudUrl;
            $data['cloudUrl']     = rtrim($frontendUrl, '/');
            $data['instanceName'] = $this->orchesryCloudInstanceName;
        }

        return new JsonResponse($data);
    }

}
