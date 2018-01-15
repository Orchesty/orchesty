<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector\BigcommerceCreateCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class BigCommerceCreateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigCommerceCreateCustomerConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $conn = $this->getConnectorMock(function (RequestDto $dto) {
            $expt = new RequestDto(CurlManager::METHOD_POST,
                new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/customers'));
            $expt->setHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'X-Auth-Client' => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
                'X-Auth-Token'  => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
            ])->setBody(json_encode([
                "email"      => "eml@eml.com",
                "first_name" => "qwe",
                "last_name"  => "asd",
            ]));

            $this->assertEquals($expt, $dto);

            return new ResponseDto(201, '', '', []);
        });

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            "email"      => "eml@eml.com",
            "first_name" => "qwe",
            "last_name"  => "asd",
        ]))->setHeaders([]);

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testProcessActionWithLimiter(): void
    {
        $headers = $this->getConnectorMock(function (RequestDto $dto, array $options = []): void {
            $this->assertEquals(
                new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/customers'),
                $dto->getUri()
            );

            throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(509));
        })->processAction((new ProcessDto())->setData('{"id":1}')->setHeaders([]))->getHeaders();

        $this->assertEquals(['pf-result-code' => 1004], $headers);
    }

    /**
     *
     */
    public function testProcessActionWithException(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::REQUEST_FAILED);

        $this->getConnectorMock(
            function (RequestDto $dto, array $options = []): void {
                $this->assertEquals(
                    new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/customers'),
                    $dto->getUri()
                );

                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(401));
            },
            function (string $type, array $content): void {
                $this->assertEquals('access_expiration', $type);
                $this->assertEquals([
                    'notification_type' => 'access_expiration',
                    'guid'              => 'User',
                    'token'             => 'Token',
                    'system_key'        => 'bigcommerce',
                    'system_name'       => 'Bigcommerce',
                ], $content);
            }
        )->processAction((new ProcessDto())->setData('{"id":1}')->setHeaders([]));
    }

    /**
     * @param callable      $curlCallback
     * @param callable|NULL $loggerCallback
     *
     * @return BigcommerceCreateCustomerConnector
     */
    private function getConnectorMock(
        callable $curlCallback,
        ?callable $loggerCallback = NULL
    ): BigcommerceCreateCustomerConnector
    {
        $dto = (new RequestDto('POST', new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/')))
            ->setHeaders([
                'X-Auth-Client' => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
                'X-Auth-Token'  => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);

        /** @var BigcommerceSystem|MockObject $system */
        $system = $this->createPartialMock(BigcommerceSystem::class, ['getRequestDto']);
        $system->method('getRequestDto')->willReturn($dto);

        /** @var SystemInstall|MockObject $systemInstall */
        $systemInstall = $this->createPartialMock(SystemInstallRepository::class, ['getSystemInstallFromHeaders']);
        $systemInstall->method('getSystemInstallFromHeaders')->willReturn(
            (new SystemInstall())
                ->setUser('User')
                ->setToken('Token')
        );

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')->willReturnCallback($curlCallback);

        $connector = new BigcommerceCreateCustomerConnector($system, $documentManager, $curlManager);

        if ($loggerCallback) {
            /** @var LoggerInterface|MockObject $logger */
            $logger = $this->createMock(LoggerInterface::class);
            $logger->method('info')->willReturnCallback($loggerCallback);
            $connector->setLogger($logger);
        }

        return $connector;
    }

}