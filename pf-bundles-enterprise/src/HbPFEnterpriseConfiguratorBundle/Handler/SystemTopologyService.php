<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class SystemTopologyService
 *
 * Triggers Orchesty system topologies by name for transactional emails and notifications.
 * Routes to local starting-point (on-prem) or a dedicated cloud Orchesty instance.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class SystemTopologyService
{

    private const string RUN_BY_NAME_ENDPOINT = 'topologies/%s/nodes/%s/user/%s/run-by-name';

    private DocumentManager $dm;

    /**
     * @param CurlManagerInterface $curlManager
     * @param DatabaseManagerLocator $dml
     * @param string $systemOrchestyUrl
     * @param string $startingPointHost
     * @param string $frontendHost
     * @param string $cloudFrontendUrl
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly CurlManagerInterface $curlManager,
        DatabaseManagerLocator $dml,
        private readonly string $systemOrchestyUrl,
        private readonly string $startingPointHost,
        private readonly string $frontendHost,
        private readonly string $cloudFrontendUrl = '',
        private readonly ?LoggerInterface $logger = NULL,
    )
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->getDm();
        $this->dm = $dm;
    }

    /**
     * @param string $topologyName
     * @param string $nodeName
     * @param mixed[] $data
     * @param string $user
     *
     * @return mixed[]
     */
    public function triggerTopology(
        string $topologyName,
        string $nodeName,
        array $data,
        string $user = ApplicationController::SYSTEM_USER,
    ): array
    {
        $baseUrl = $this->resolveBaseUrl();
        $path    = sprintf(self::RUN_BY_NAME_ENDPOINT, $topologyName, $nodeName, $user);
        $url     = sprintf('%s/%s', rtrim($baseUrl, '/'), $path);

        $headers = $this->getHeaders();
        $body    = json_encode($data, JSON_THROW_ON_ERROR);

        try {
            $request = new RequestDto(
                new Uri($url),
                CurlManager::METHOD_POST,
                new ProcessDto(),
                $body,
                $headers,
            );

            $response = $this->curlManager->send($request);

            return [
                'success'    => $response->getStatusCode() === 200,
                'statusCode' => $response->getStatusCode(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error(
                sprintf(
                    'SystemTopologyService: Failed to trigger topology [%s/%s]: %s',
                    $topologyName,
                    $nodeName,
                    $e->getMessage(),
                ),
            );

            return [
                'success' => FALSE,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * @param string $email
     * @param string $hash
     *
     * @return mixed[]
     */
    public function sendInviteEmail(string $email, string $hash): array
    {
        $frontendUrl = $this->resolveEmailFrontendUrl();

        return $this->triggerTopology(
            'system-transaction-emails',
            'invite',
            [
                'email'       => $email,
                'hash'        => $hash,
                'frontendUrl' => $frontendUrl,
                'cloudMode'   => $this->isCloudMode(),
            ],
        );
    }

    /**
     * @param string $email
     * @param string $hash
     *
     * @return mixed[]
     */
    public function sendForgotPasswordEmail(string $email, string $hash): array
    {
        return $this->triggerTopology(
            'system-transaction-emails',
            'forgot-password',
            [
                'email'       => $email,
                'hash'        => $hash,
                'frontendUrl' => $this->resolveEmailFrontendUrl(),
            ],
        );
    }

    /**
     * @param string $email
     *
     * @return mixed[]
     */
    public function sendRestoreAccessEmail(string $email): array
    {
        return $this->triggerTopology(
            'system-transaction-emails',
            'restore-access',
            [
                'email'       => $email,
                'frontendUrl' => $this->resolveEmailFrontendUrl(),
            ],
        );
    }

    private function isCloudMode(): bool
    {
        return $this->cloudFrontendUrl !== '';
    }

    private function resolveEmailFrontendUrl(): string
    {
        if ($this->isCloudMode()) {
            return rtrim($this->cloudFrontendUrl, '/');
        }

        return rtrim($this->frontendHost, '/');
    }

    private function resolveBaseUrl(): string
    {
        if ($this->systemOrchestyUrl !== '') {
            return $this->systemOrchestyUrl;
        }

        return $this->startingPointHost;
    }

    /**
     * @return mixed[]
     */
    private function getHeaders(): array
    {
        $apiToken = $this->dm->getRepository(ApiToken::class)->findOneBy(
            [
                'scopes' => ApiTokenScopesEnum::TOPOLOGY_RUN,
                'user'   => ApplicationController::SYSTEM_USER,
            ],
        );

        if ($apiToken) {
            return [
                'orchesty-api-key' => $apiToken->getKey(),
            ];
        }

        return [];
    }

}
