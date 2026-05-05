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
 * Class DocsReadClient
 *
 * Server-to-server HTTP client for the Trace assistant's `docs_read` MCP
 * tool. Companion to DocsSearchClient: when the assistant has identified a
 * documentation page via `docs_search` but the bodyExcerpt is too thin to
 * answer the user's question, it can ask `docs_read` for the full body of
 * a single page (up to ~12000 chars).
 *
 * Configuration: the same DOCS_SEARCH_URL / DOCS_SEARCH_TOKEN env pair as
 * DocsSearchClient — both clients share the Nuxt origin and shared secret.
 * When DOCS_SEARCH_URL is empty, the tool is hidden from the MCP manifest.
 *
 * Failure handling: every failure (network, non-2xx, malformed JSON) is
 * converted to a {body: '', error: '...'} payload rather than throwing, so
 * the Trace summariser can degrade gracefully instead of surfacing a stack
 * trace to the user.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class DocsReadClient
{

    private const int    REQUEST_TIMEOUT_S  = 5;
    private const string READ_PATH          = '/api/docs/read';
    private const string TRACE_TOKEN_HEADER = 'X-Trace-Token';

    /**
     * DocsReadClient constructor.
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
     * `docs_read` from the manifest entirely. Mirrors DocsSearchClient::isConfigured.
     */
    public function isConfigured(): bool
    {
        return trim($this->baseUrl) !== '';
    }

    /**
     * Fetch full body text of a single docs / learn / onboarding page.
     *
     * Always returns an array shaped roughly like:
     *   {
     *     path: '/docs/2.0/...',
     *     title: '...',
     *     description: '...',
     *     body: '...',          // up to ~12000 chars, possibly trimmed
     *     truncated: bool,
     *     latestVersion: '2.0',
     *     error?: '...'
     *   }
     *
     * @param string $path canonical path returned by docs_search (`/docs/<latest>/...`, `/learn/...`, `/onboarding/...`)
     *
     * @return mixed[]
     */
    public function read(string $path): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'docs read is not configured', 'path' => $path, 'body' => ''];
        }

        $trimmedPath = trim($path);
        if ($trimmedPath === '') {
            return ['path' => '', 'body' => ''];
        }

        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($this->sharedToken !== '') {
            $headers[self::TRACE_TOKEN_HEADER] = $this->sharedToken;
        }

        $url = sprintf('%s%s', rtrim($this->baseUrl, '/'), self::READ_PATH);
        $dto = (new RequestDto(new Uri($url), CurlManager::METHOD_POST, new ProcessDto()))
            ->setHeaders($headers)
            ->setBody((string) json_encode(['path' => $trimmedPath], JSON_THROW_ON_ERROR));

        try {
            // HTTP_ERRORS=false lets us inspect non-2xx ourselves so we can
            // wrap them into the "doc not available, here's why" envelope
            // instead of letting Guzzle throw across the MCP boundary.
            $response = $this->curlManager->send(
                $dto,
                [
                    RequestOptions::HTTP_ERRORS => FALSE,
                    RequestOptions::TIMEOUT     => self::REQUEST_TIMEOUT_S,
                ],
            );
        } catch (Throwable $e) {
            return [
                'error' => sprintf('docs read request failed: %s', $e->getMessage()),
                'path'  => $trimmedPath,
                'body'  => '',
            ];
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return [
                'error' => sprintf('docs read returned status %d', $status),
                'path'  => $trimmedPath,
                'body'  => '',
            ];
        }

        try {
            $decoded = $response->getJsonBody();
        } catch (Throwable $e) {
            return [
                'error' => sprintf('docs read returned malformed JSON: %s', $e->getMessage()),
                'path'  => $trimmedPath,
                'body'  => '',
            ];
        }

        if (!isset($decoded['body']) || !is_string($decoded['body'])) {
            $decoded['body'] = '';
        }
        $decoded['path'] = $decoded['path'] ?? $trimmedPath;

        return $decoded;
    }

}
