<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector\ShopifyCreateCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShopifyCreateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
final class ShopifyCreateCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new ShopifyCreateCustomerConnector(
            $this->container->get('systems.shopify'),
            $this->mockDm(),
            $this->mockCurl(function (RequestDto $requestDto): ResponseDto {
                $expt = new RequestDto('POST', new Uri('https://hbpf.myshopify.com/admin/customers.json'));
                $expt->setHeaders([
                    'Content-Type'           => 'application/json',
                    'X-Shopify-Access-Token' => 'asd',
                ])->setBody(json_encode([
                    'customer' => [
                        'email' => 'eml@eml.com',
                        'name'  => 'first last',
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                return new ResponseDto(201, '', '{"customer":{}}', []);
            })
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            'customer' => [
                'email' => 'eml@eml.com',
                'name'  => 'first last',
            ],
        ]));

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testConnectorWithException(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::REQUEST_FAILED);

        $conn = new ShopifyCreateCustomerConnector(
            $this->container->get('systems.shopify'),
            $this->mockDm(),
            $this->mockCurl(function (RequestDto $requestDto): void {
                $expt = new RequestDto('POST', new Uri('https://hbpf.myshopify.com/admin/customers.json'));
                $expt->setHeaders([
                    'Content-Type'           => 'application/json',
                    'X-Shopify-Access-Token' => 'asd',
                ])->setBody(json_encode([
                    'customer' => [
                        'email' => 'eml@eml.com',
                        'name'  => 'first last',
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(401));
            })
        );

        $conn->setLogger($this->mockLogger());

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            'customer' => [
                'email' => 'eml@eml.com',
                'name'  => 'first last',
            ],
        ]));

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testConnectorWithLimiter(): void
    {
        $conn = new ShopifyCreateCustomerConnector(
            $this->container->get('systems.shopify'),
            $this->mockDm(),
            $this->mockCurl(function (RequestDto $requestDto): void {
                $expt = new RequestDto('POST', new Uri('https://hbpf.myshopify.com/admin/customers.json'));
                $expt->setHeaders([
                    'Content-Type'           => 'application/json',
                    'X-Shopify-Access-Token' => 'asd',
                ])->setBody(json_encode([
                    'customer' => [
                        'email' => 'eml@eml.com',
                        'name'  => 'first last',
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(429));
            })
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            'customer' => [
                'email' => 'eml@eml.com',
                'name'  => 'first last',
            ],
        ]));

        $headers = $conn->processAction($dto)->getHeaders();
        $this->assertEquals(['pf-result-code' => 1004], $headers);
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setUser('User')
            ->setToken('Token')
            ->setSettings([
                'system_url'   => 'hbpf',
                'access_token' => 'asd',
            ]);

        $repo = $this->createPartialMock(SystemInstallRepository::class, ['getSystemInstallFromHeaders']);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @param callable $callback
     *
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl(callable $callback): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())->method('send')->willReturnCallback($callback);

        return $curl;
    }

    /**
     * @return LoggerInterface
     */
    private function mockLogger(): LoggerInterface
    {
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')->willReturnCallback(function (string $type, array $content): void {
                $this->assertEquals('access_expiration', $type);
                $this->assertEquals([
                    'guid'        => 'User',
                    'token'       => 'Token',
                    'system_key'  => 'shopify',
                    'system_name' => 'Shopify',
                ], $content);
            });

        return $logger;
    }

}