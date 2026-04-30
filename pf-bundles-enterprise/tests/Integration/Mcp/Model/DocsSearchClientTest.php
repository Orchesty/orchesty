<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Mcp\Model;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Model\DocsSearchClient;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class DocsSearchClientTest
 *
 * Unit-level coverage of the Trace docs-search HTTP client. Real
 * orchesty.io is stubbed via a CurlManager mock so the test asserts the
 * outgoing request shape (URL, method, headers, JSON body) and the failure
 * envelope returned for non-2xx and transport errors.
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Mcp\Model
 */
#[CoversClass(DocsSearchClient::class)]
#[AllowMockObjectsWithoutExpectations]
final class DocsSearchClientTest extends TestCase
{

    /**
     * Verifies that the client is only considered configured when a base URL is provided.
     */
    public function testIsConfiguredOnlyWhenUrlIsPresent(): void
    {
        $configured = new DocsSearchClient(
            $this->createMock(CurlManagerInterface::class),
            'https://orchesty.io',
            'secret',
        );
        self::assertTrue($configured->isConfigured());

        $noUrl = new DocsSearchClient($this->createMock(CurlManagerInterface::class), '', 'secret');
        self::assertFalse($noUrl->isConfigured());

        // Empty token is allowed (Nuxt accepts unauthenticated calls in dev
        // mode), so URL alone is enough to count as configured.
        $noToken = new DocsSearchClient($this->createMock(CurlManagerInterface::class), 'https://orchesty.io', '');
        self::assertTrue($noToken->isConfigured());
    }

    /**
     * Verifies that calling search on an unconfigured client returns a synthetic error envelope.
     */
    public function testSearchReturnsErrorWhenUnconfigured(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects(self::never())->method('send');

        $client = new DocsSearchClient($curl, '', 'secret');
        $result = $client->search('how do I get started');

        self::assertSame([], $result['results']);
        self::assertArrayHasKey('error', $result);
        self::assertSame('how do I get started', $result['query']);
    }

    /**
     * Verifies that a blank query short-circuits before any HTTP traffic.
     */
    public function testSearchSkipsHttpForBlankQuery(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects(self::never())->method('send');

        $client = new DocsSearchClient($curl, 'https://orchesty.io', 'secret');
        $result = $client->search('   ');

        self::assertSame([], $result['results']);
        self::assertSame('', $result['query']);
    }

    /**
     * Verifies that the outgoing request URL, method, headers and JSON body match the expected shape.
     */
    public function testSearchForwardsRequestShape(): void
    {
        $captured = NULL;

        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (RequestDto $dto) use (&$captured): ResponseDto {
                $captured = $dto;

                return new ResponseDto(
                    200,
                    '',
                    Json::encode([
                        'latestVersion' => '2.0',
                        'locale'        => 'cs',
                        'query'         => 'how do I get started',
                        'results'       => [
                            [
                                'description' => 'desc',
                                'path'        => '/learn/get-started/get-started',
                                'score'       => 12,
                                'snippet'     => 'snippet',
                                'source'      => 'learn',
                                'title'       => 'Get Started',
                            ],
                        ],
                    ]),
                    [],
                );
            });

        $client = new DocsSearchClient($curl, 'https://orchesty.io/', 'top-secret');
        $result = $client->search('how do I get started', 3, 'cs');

        self::assertNotNull($captured);
        self::assertSame('https://orchesty.io/api/docs/search', (string) $captured->getUri());
        self::assertSame(CurlManager::METHOD_POST, $captured->getMethod());

        $headers = $captured->getHeaders();
        self::assertSame('application/json', $headers['Content-Type']);
        self::assertSame('top-secret', $headers['X-Trace-Token']);

        $body = Json::decode($captured->getBody());
        self::assertSame('how do I get started', $body['q']);
        self::assertSame(3, $body['topK']);
        self::assertSame('cs', $body['locale']);

        self::assertSame('2.0', $result['latestVersion']);
        self::assertCount(1, $result['results']);
        self::assertSame('Get Started', $result['results'][0]['title']);
    }

    /**
     * Verifies that the X-Trace-Token header is omitted when the configured secret is blank.
     */
    public function testSearchOmitsTokenHeaderWhenSecretIsBlank(): void
    {
        $captured = NULL;

        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')
            ->willReturnCallback(static function (RequestDto $dto) use (&$captured): ResponseDto {
                $captured = $dto;

                return new ResponseDto(200, '', Json::encode(['results' => []]), []);
            });

        $client = new DocsSearchClient($curl, 'https://orchesty.io', '');
        $client->search('hello');

        self::assertNotNull($captured);
        self::assertArrayNotHasKey('X-Trace-Token', $captured->getHeaders());
    }

    /**
     * Verifies that the topK argument is clamped to the supported minimum and maximum bounds.
     */
    public function testSearchClampsTopkBetweenMinAndMax(): void
    {
        $captured = NULL;

        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')
            ->willReturnCallback(static function (RequestDto $dto) use (&$captured): ResponseDto {
                $captured = $dto;

                return new ResponseDto(200, '', Json::encode(['results' => []]), []);
            });

        $client = new DocsSearchClient($curl, 'https://orchesty.io', 'secret');
        $client->search('q', 999);

        self::assertNotNull($captured);
        $body = Json::decode($captured->getBody());
        self::assertSame(DocsSearchClient::MAX_TOP_K, $body['topK']);

        $client->search('q', 0);
        $body = Json::decode($captured->getBody());
        self::assertSame(1, $body['topK']);
    }

    /**
     * Verifies that an unsupported locale is dropped from the outgoing payload.
     */
    public function testSearchSkipsLocaleWhenInvalid(): void
    {
        $captured = NULL;

        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')
            ->willReturnCallback(static function (RequestDto $dto) use (&$captured): ResponseDto {
                $captured = $dto;

                return new ResponseDto(200, '', Json::encode(['results' => []]), []);
            });

        $client = new DocsSearchClient($curl, 'https://orchesty.io', 'secret');
        $client->search('q', 5, 'fr');

        self::assertNotNull($captured);
        $body = Json::decode($captured->getBody());
        self::assertArrayNotHasKey('locale', $body);
    }

    /**
     * Verifies that a non-2xx HTTP response is mapped to a synthetic error envelope.
     */
    public function testSearchHandlesNon2xxAsErrorEnvelope(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')
            ->willReturn(new ResponseDto(500, '', 'oops', []));

        $client = new DocsSearchClient($curl, 'https://orchesty.io', 'secret');
        $result = $client->search('hello');

        self::assertSame([], $result['results']);
        self::assertStringContainsString('status 500', $result['error']);
        self::assertSame('hello', $result['query']);
    }

    /**
     * Verifies that a thrown transport exception is mapped to a synthetic error envelope.
     */
    public function testSearchHandlesTransportExceptionAsErrorEnvelope(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')
            ->willThrowException(
                new ConnectException(
                    'boom',
                    new Request('POST', 'https://orchesty.io/api/docs/search'),
                ),
            );

        $client = new DocsSearchClient($curl, 'https://orchesty.io', 'secret');
        $result = $client->search('hello');

        self::assertSame([], $result['results']);
        self::assertStringContainsString('boom', $result['error']);
    }

    /**
     * Verifies that a missing results key is normalised to an empty list while other fields pass through.
     */
    public function testSearchNormalisesResultsWhenAbsent(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')
            ->willReturn(new ResponseDto(200, '', Json::encode(['latestVersion' => '2.0']), []));

        $client = new DocsSearchClient($curl, 'https://orchesty.io', 'secret');
        $result = $client->search('hello');

        self::assertSame([], $result['results']);
        self::assertSame('2.0', $result['latestVersion']);
        self::assertSame('hello', $result['query']);
    }

}
