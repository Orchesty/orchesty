<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package Tests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
    }

    /**
     * @param string $baseUrl
     * @param string $clientId
     * @param string $scopes
     *
     * @return MockObject
     */
    protected function mockRedirect(string $baseUrl, string $clientId, string $scopes = ''): MockObject
    {
        if (!empty($scopes)) {
            $scopes = sprintf('&scope=%s', $scopes);
        }

        $redirectUrl = 'https://127.0.0.11/applications/authorize/token';
        $expectedUrl = sprintf(
            '%s?response_type=code&approval_prompt=auto&redirect_uri=%s&client_id=%s%s&state=state&access_type=offline',
            $baseUrl,
            $redirectUrl,
            $clientId,
            $scopes
        );

        $mock = self::createMock(RedirectInterface::class);
        $mock->method('make')->willReturnCallback(
            function (string $url) use ($expectedUrl): void {
                $url = preg_replace('/state=[a-zA-Z0-9].*\&/', 'state=state&', $url, 1);
                self::assertEquals($expectedUrl, $url);
            }
        );
        self::$container->set('hbpf.redirect', $mock);

        return $mock;
    }

    /**
     * @param array $array
     *
     * @return MockObject
     */
    protected function mockCurl(array $array): MockObject
    {
        $mock = self::createMock(CurlManagerInterface::class);
        foreach ($array as $key => $mockCurlMethod) {
            $mock->expects(self::at($key))->method('send')->willReturnCallback(
                function (RequestDto $dto, array $options = []) use ($mockCurlMethod): ResponseDto {
                    $dto;
                    $options;

                    $body = $this->getFile($mockCurlMethod->getFileName());

                    return new ResponseDto(
                        $mockCurlMethod->getCode(),
                        '',
                        $body,
                        $mockCurlMethod->getHeaders()
                    );
                }
            );
        }
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

        return (string) file_get_contents(sprintf('%s/Data/%s',
            implode('/', $exploded),
            $fileName,
        ), TRUE);
    }

    /**
     * @param ProcessDto $response
     * @param string     $fileName
     */
    protected function assertSuccessProcessResponse(ProcessDto $response, string $fileName): void
    {
        self::assertProcessResponse(
            $response,
            $fileName,
            );

        self::assertArrayNotHasKey('pf-result-code', $response->getHeaders());
    }

    /**
     * @param ProcessDto $response
     * @param string     $fileName
     */
    protected function assertFailedProcessResponse(ProcessDto $response, string $fileName): void
    {
        self::assertProcessResponse(
            $response,
            $fileName,
            );

        self::assertEquals($response->getHeaders()['pf-result-code'], ProcessDto::STOP_AND_FAILED);
    }

    /**
     * @param ProcessDto $response
     * @param string     $fileName
     */
    private function assertProcessResponse(ProcessDto $response, string $fileName): void
    {
        $json = $this->getFile($fileName);

        $json         = json_decode((string) $json, TRUE, 512, JSON_THROW_ON_ERROR);
        $responseJson = json_decode((string) $response->getData(), TRUE, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($json, $responseJson);
    }

}
