<?php declare(strict_types=1);

namespace HbPFConnectorsTests;

use Closure;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class KernelTestCaseAbstract
 *
 * @package HbPFConnectorsTests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    /**
     * @param string $baseUrl
     * @param string $clientId
     * @param string $scopes
     *
     * @return RedirectInterface
     */
    protected function mockRedirect(string $baseUrl, string $clientId, string $scopes = ''): RedirectInterface
    {
        if (!empty($scopes)) {
            $scopes = sprintf('&scope=%s', $scopes);
        }

        $redirectUrl = 'https://127.0.0.11/api/applications/authorize/token';
        $expectedUrl = sprintf(
            '%s?response_type=code&approval_prompt=auto&redirect_uri=%s&client_id=%s%s&state=state&access_type=offline',
            $baseUrl,
            $redirectUrl,
            $clientId,
            $scopes
        );

        $mock = self::createMock(RedirectInterface::class);
        $mock->method('make')->willReturnCallback(
            static function (string $url) use ($expectedUrl): void {
                $url = preg_replace('/state=[a-zA-Z0-9].*&/', 'state=state&', $url, 1);
                self::assertEquals($expectedUrl, $url);
            }
        );
        self::$container->set('hbpf.redirect', $mock);

        return $mock;
    }

    /**
     * @param mixed[] $array
     *
     * @return CurlManagerInterface
     */
    protected function mockCurl(array $array): CurlManagerInterface
    {
        $mock = self::createMock(CurlManagerInterface::class);

        $mock
            ->expects(self::exactly(count($array)))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                ...array_map(
                    fn($mockCurlMethod) => new ReturnCallback(
                        fn(): ResponseDto => new ResponseDto(
                            $mockCurlMethod->getCode(),
                            '',
                            $this->getFile($mockCurlMethod->getFileName()),
                            $mockCurlMethod->getHeaders()
                        )
                    ),
                    $array
                )
            );

        self::$container->set('hbpf.transport.curl_manager', $mock);

        return $mock;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getFile(string $fileName): string
    {
        $exploded = explode('\\', static::class);
        array_pop($exploded);
        array_shift($exploded);

        return (string) file_get_contents(
            sprintf(
                '%s/Data/%s',
                implode('/', $exploded),
                $fileName,
            ),
            TRUE
        );
    }

    /**
     * @param ProcessDto $response
     * @param string     $fileName
     *
     * @throws Exception
     */
    protected function assertSuccessProcessResponse(ProcessDto $response, string $fileName): void
    {
        self::assertProcessResponse($response, $fileName);
        self::assertArrayNotHasKey('pf-result-code', $response->getHeaders());
    }

    /**
     * @param ProcessDto $response
     * @param string     $fileName
     *
     * @throws Exception
     */
    protected function assertFailedProcessResponse(ProcessDto $response, string $fileName): void
    {
        self::assertProcessResponse($response, $fileName);
        self::assertEquals($response->getHeaders()['pf-result-code'], ProcessDto::STOP_AND_FAILED);
    }

    /**
     * @phpstan-param class-string<Throwable> $exception
     *
     * @param string      $exception
     * @param int|null    $exceptionCode
     * @param string|null $exceptionMessage
     * @param bool        $isExact
     */
    protected function assertException(
        string $exception,
        ?int $exceptionCode = NULL,
        ?string $exceptionMessage = NULL,
        bool $isExact = TRUE
    ): void
    {
        self::expectException($exception);

        if ($exceptionCode) {
            self::expectExceptionCode($exceptionCode);
        }

        if ($exceptionMessage) {
            $isExact ?
                self::expectExceptionMessageMatches(sprintf('/^%s$/', preg_quote($exceptionMessage))) :
                self::expectExceptionMessageMatches($exceptionMessage);
        }
    }

    /**
     * @param BatchInterface $batch
     * @param ProcessDto     $dto
     * @param Closure|null   $closure
     */
    protected function assertBatch(BatchInterface $batch, ProcessDto $dto, ?Closure $closure = NULL): void
    {
        $batch->processBatch(
            $dto,
            $closure ?: static function (): void {
                self::assertTrue(TRUE);
            }
        )->then(
            static function (): void {
                self::assertTrue(TRUE);
            },
            static function ($e): void {
                self::fail(sprintf('Something gone wrong!: %s', $e));
            }
        )->wait();
    }

    /**
     * @param Closure ...$closures
     *
     * @return CurlManager
     */
    protected function prepareSender(Closure ...$closures): CurlManager
    {
        $sender = self::createPartialMock(CurlManager::class, ['send']);
        $sender
            ->expects(self::exactly(count($closures)))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                ...array_map(static fn(Closure $closure) => new ReturnCallback($closure), $closures)
            );

        return $sender;
    }

    /**
     * @param mixed[]|string $data
     * @param string|null    $url
     *
     * @return Closure
     */
    protected function prepareSenderResponse($data = '{}', ?string $url = NULL): Closure
    {
        return static function (RequestDto $dto) use ($data, $url): ResponseDto {
            if ($url) {
                self::assertEquals($url, sprintf('%s %s', $dto->getMethod(), $dto->getUri(TRUE)));
            }

            return new ResponseDto(200, 'OK', is_array($data) ? Json::encode($data) : $data, []);
        };
    }

    /**
     * @param string $message
     *
     * @return Closure
     */
    protected function prepareSenderErrorResponse(string $message = 'Something gone wrong!'): Closure
    {
        return static function () use ($message): void {
            throw new CurlException($message, CurlException::REQUEST_FAILED);
        };
    }

    /**
     * @param mixed[]|string $data
     * @param mixed[]        $headers
     *
     * @return ProcessDto
     */
    protected function prepareProcessDto($data = [], $headers = []): ProcessDto
    {
        return (new ProcessDto())->setData(is_array($data) ? Json::encode($data) : $data)->setHeaders($headers);
    }

    /**
     * @param ProcessDto $response
     * @param string     $fileName
     *
     * @throws Exception
     */
    private function assertProcessResponse(ProcessDto $response, string $fileName): void
    {
        $json = $this->getFile($fileName);

        $json         = Json::decode($json);
        $responseJson = Json::decode($response->getData());

        self::assertEquals($json, $responseJson);
    }

}
