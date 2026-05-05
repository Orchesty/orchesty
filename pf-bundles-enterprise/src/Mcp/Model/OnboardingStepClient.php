<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Throwable;

/**
 * Class OnboardingStepClient
 *
 * Server-to-server HTTP client for the Trace assistant's `onboarding_step`
 * MCP tool. Returns a structured onboarding step (title, intro, actions[],
 * next) sourced from `content/onboarding/*.md` on the public Orchesty site.
 *
 * The wizard content lives in Markdown so the content team can iterate on
 * onboarding without a backend deploy. The Trace summariser renders the
 * structured payload into copy-pasteable [shell] / [prompt] / [link] blocks
 * the FE drawer recognises.
 *
 * Configuration: shares DOCS_SEARCH_URL / DOCS_SEARCH_TOKEN with the docs
 * clients — same Nuxt origin, same shared secret. When DOCS_SEARCH_URL is
 * empty the tool is hidden from the MCP manifest.
 *
 * Failure handling: every failure (network, non-2xx, malformed JSON) is
 * converted to a {stages: [], actions: [], error: '...'} payload rather
 * than throwing, so the Trace summariser can degrade gracefully.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class OnboardingStepClient
{

    private const int    REQUEST_TIMEOUT_S  = 5;
    private const string STEP_PATH          = '/api/onboarding/step';
    private const string TRACE_TOKEN_HEADER = 'X-Trace-Token';

    /**
     * OnboardingStepClient constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param string               $baseUrl     Nuxt origin without trailing slash; empty disables the tool.
     * @param string               $sharedToken Shared secret for the X-Trace-Token header.
     */
    public function __construct(
        private readonly CurlManagerInterface $curlManager,
        private readonly string $baseUrl,
        private readonly string $sharedToken,
    )
    {
    }

    /**
     * Whether the tool is configured. When false, McpManager omits
     * `onboarding_step` from the manifest entirely.
     */
    public function isConfigured(): bool
    {
        return trim($this->baseUrl) !== '';
    }

    /**
     * Fetch a single onboarding step.
     *
     * Always returns an array shaped roughly like:
     *   {
     *     stage: 'overview',
     *     title: '...',
     *     description: '...',
     *     intro: '...',
     *     prerequisites: ['...'],
     *     next: 'install-tools' | null,
     *     actions: [{ kind: 'shell'|'prompt'|'link', label, value?, href? }],
     *     stages: ['overview', 'install-tools', ...],
     *     path: '/onboarding/...',
     *     error?: '...'
     *   }
     *
     * @param string|null $stage  optional stage id; null/empty defaults to the first stage
     *
     * @return mixed[]
     */
    public function step(?string $stage = NULL): array
    {
        if (!$this->isConfigured()) {
            return [
                'error'   => 'onboarding step is not configured',
                'stages'  => [],
                'actions' => [],
            ];
        }

        $body = [];
        if ($stage !== NULL) {
            $trimmed = trim($stage);
            if ($trimmed !== '') {
                $body['stage'] = $trimmed;
            }
        }

        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($this->sharedToken !== '') {
            $headers[self::TRACE_TOKEN_HEADER] = $this->sharedToken;
        }

        $url = sprintf('%s%s', rtrim($this->baseUrl, '/'), self::STEP_PATH);
        $dto = (new RequestDto(new Uri($url), CurlManager::METHOD_POST, new ProcessDto()))
            ->setHeaders($headers)
            ->setBody((string) json_encode($body, JSON_THROW_ON_ERROR));

        try {
            // HTTP_ERRORS=false lets us inspect non-2xx ourselves so we can
            // wrap them into a degraded envelope rather than letting Guzzle
            // throw across the MCP boundary.
            $response = $this->curlManager->send(
                $dto,
                [
                    RequestOptions::HTTP_ERRORS => FALSE,
                    RequestOptions::TIMEOUT     => self::REQUEST_TIMEOUT_S,
                ],
            );
        } catch (Throwable $e) {
            return [
                'error'   => sprintf('onboarding step request failed: %s', $e->getMessage()),
                'stages'  => [],
                'actions' => [],
            ];
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return [
                'error'   => sprintf('onboarding step returned status %d', $status),
                'stages'  => [],
                'actions' => [],
            ];
        }

        try {
            $decoded = $response->getJsonBody();
        } catch (Throwable $e) {
            return [
                'error'   => sprintf('onboarding step returned malformed JSON: %s', $e->getMessage()),
                'stages'  => [],
                'actions' => [],
            ];
        }

        if (!isset($decoded['actions']) || !is_array($decoded['actions'])) {
            $decoded['actions'] = [];
        }
        if (!isset($decoded['stages']) || !is_array($decoded['stages'])) {
            $decoded['stages'] = [];
        }

        return $decoded;
    }

}
