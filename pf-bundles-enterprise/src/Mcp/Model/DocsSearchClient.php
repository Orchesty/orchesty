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
 * Class DocsSearchClient
 *
 * Server-to-server HTTP client for the Trace assistant's `docs_search` MCP
 * tool. Forwards a natural-language query to the public Orchesty docs site
 * (orchesty.io, Nuxt Content) which returns top-K documentation snippets
 * scored against title / description / tags / body text.
 *
 * Configuration:
 *   - DOCS_SEARCH_URL    base URL of the Nuxt site (e.g. https://orchesty.io).
 *                        When empty the tool is hidden from the MCP manifest
 *                        so on-prem deployments without internet access don't
 *                        advertise a tool that would always fail.
 *   - DOCS_SEARCH_TOKEN  shared secret forwarded as `X-Trace-Token`. Must
 *                        match the Nuxt-side `NUXT_TRACE_SEARCH_TOKEN`. When
 *                        empty the Nuxt endpoint accepts the call anyway
 *                        (dev mode), but production must always set both.
 *
 * Failure handling: every failure (network, non-2xx, malformed JSON) is
 * converted to a {results: [], error: '...'} payload rather than throwing.
 * The Trace summariser then explains "no docs matched" to the user instead
 * of surfacing an internal stack trace.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class DocsSearchClient
{

    public const int DEFAULT_TOP_K = 5;
    public const int MAX_TOP_K     = 10;

    private const int    REQUEST_TIMEOUT_S  = 5;
    private const string SEARCH_PATH        = '/api/docs/search';
    private const string TRACE_TOKEN_HEADER = 'X-Trace-Token';

    /**
     * DocsSearchClient constructor.
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
     * `docs_search` from the manifest entirely.
     *
     * The token alone does not block configuration — Nuxt allows empty
     * tokens in dev mode — but the URL is required.
     */
    public function isConfigured(): bool
    {
        return trim($this->baseUrl) !== '';
    }

    /**
     * Run a docs search and return the parsed Nuxt response.
     *
     * Always returns an array shaped roughly like:
     *   {
     *     results: [{ path, title, description, snippet, score, source }],
     *     latestVersion: '2.0',
     *     locale: 'en',
     *     query: '...',
     *     error?: '...'
     *   }
     *
     * @param string      $query  natural-language query, passed verbatim to the LLM
     * @param int         $topK   max results to return (capped at MAX_TOP_K)
     * @param string|null $locale 'cs'|'en' hint forwarded to the endpoint
     *
     * @return mixed[]
     */
    public function search(string $query, int $topK = self::DEFAULT_TOP_K, ?string $locale = NULL): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'docs search is not configured', 'query' => $query, 'results' => []];
        }

        $trimmedQuery = trim($query);
        if ($trimmedQuery === '') {
            return ['query' => '', 'results' => []];
        }

        $body = [
            'q'    => $trimmedQuery,
            'topK' => max(1, min(self::MAX_TOP_K, $topK)),
        ];
        if ($locale !== NULL && ($locale === 'cs' || $locale === 'en')) {
            $body['locale'] = $locale;
        }

        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($this->sharedToken !== '') {
            $headers[self::TRACE_TOKEN_HEADER] = $this->sharedToken;
        }

        $url = sprintf('%s%s', rtrim($this->baseUrl, '/'), self::SEARCH_PATH);
        $dto = (new RequestDto(new Uri($url), CurlManager::METHOD_POST, new ProcessDto()))
            ->setHeaders($headers)
            ->setBody((string) json_encode($body, JSON_THROW_ON_ERROR));

        try {
            // HTTP_ERRORS=false lets us inspect non-2xx ourselves so we can
            // wrap them into the "no docs matched, here's why" envelope
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
                'error'   => sprintf('docs search request failed: %s', $e->getMessage()),
                'query'   => $trimmedQuery,
                'results' => [],
            ];
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return [
                'error'   => sprintf('docs search returned status %d', $status),
                'query'   => $trimmedQuery,
                'results' => [],
            ];
        }

        try {
            $decoded = $response->getJsonBody();
        } catch (Throwable $e) {
            return [
                'error'   => sprintf('docs search returned malformed JSON: %s', $e->getMessage()),
                'query'   => $trimmedQuery,
                'results' => [],
            ];
        }

        if (!isset($decoded['results']) || !is_array($decoded['results'])) {
            $decoded['results'] = [];
        }
        $decoded['query'] = $trimmedQuery;

        return $decoded;
    }

}
