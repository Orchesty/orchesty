<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector\ShopifyUpdateCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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
 * Class ShopifyUpdateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
final class ShopifyUpdateCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new ShopifyUpdateCustomerConnector(
            $this->container->get('systems.shopify'),
            $this->mockDm(),
            $this->mockCurl(function (RequestDto $requestDto): ResponseDto {
                $expt = new RequestDto('PUT', new Uri('https://hbpf.myshopify.com/admin/customers/123456.json'));
                $expt->setHeaders([
                    'Content-Type'           => 'application/json',
                    'X-Shopify-Access-Token' => 'asd',
                ])->setBody(json_encode([
                    'customer' => [
                        'metafields' => [
                            'key'        => CleverCustomKeysEnum::UNSUBSCRIBE,
                            'value'      => 1,
                            'value_type' => 'integer',
                            'namespace'  => 'global',
                        ],
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                return new ResponseDto(200, '', '{"customer":{}}', []);
            })
        );

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
        ])->setData(json_encode([
            'body' => json_encode([
                'customer' => [
                    'metafields' => [
                        'key'        => CleverCustomKeysEnum::UNSUBSCRIBE,
                        'value'      => 1,
                        'value_type' => 'integer',
                        'namespace'  => 'global',
                    ],
                ],
            ]),
            'id'   => '123456',
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

        $conn = new ShopifyUpdateCustomerConnector(
            $this->container->get('systems.shopify'),
            $this->mockDm(),
            $this->mockCurl(function (RequestDto $requestDto): void {
                $expt = new RequestDto('PUT', new Uri('https://hbpf.myshopify.com/admin/customers/123456.json'));
                $expt->setHeaders([
                    'Content-Type'           => 'application/json',
                    'X-Shopify-Access-Token' => 'asd',
                ])->setBody(json_encode([
                    'customer' => [
                        'metafields' => [
                            'key'        => CleverCustomKeysEnum::UNSUBSCRIBE,
                            'value'      => 1,
                            'value_type' => 'integer',
                            'namespace'  => 'global',
                        ],
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(401));
            })
        );

        $conn->setLogger($this->mockLogger());

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
        ])->setData(json_encode([
            'body' => json_encode([
                'customer' => [
                    'metafields' => [
                        'key'        => CleverCustomKeysEnum::UNSUBSCRIBE,
                        'value'      => 1,
                        'value_type' => 'integer',
                        'namespace'  => 'global',
                    ],
                ],
            ]),
            'id'   => '123456',
        ]));

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testConnectorWithLimiter(): void
    {
        $conn = new ShopifyUpdateCustomerConnector(
            $this->container->get('systems.shopify'),
            $this->mockDm(),
            $this->mockCurl(function (RequestDto $requestDto): void {
                $expt = new RequestDto('PUT', new Uri('https://hbpf.myshopify.com/admin/customers/123456.json'));
                $expt->setHeaders([
                    'Content-Type'           => 'application/json',
                    'X-Shopify-Access-Token' => 'asd',
                ])->setBody(json_encode([
                    'customer' => [
                        'metafields' => [
                            'key'        => CleverCustomKeysEnum::UNSUBSCRIBE,
                            'value'      => 1,
                            'value_type' => 'integer',
                            'namespace'  => 'global',
                        ],
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(429));
            })
        );

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
        ])->setData(json_encode([
            'body' => json_encode([
                'customer' => [
                    'metafields' => [
                        'key'        => CleverCustomKeysEnum::UNSUBSCRIBE,
                        'value'      => 1,
                        'value_type' => 'integer',
                        'namespace'  => 'global',
                    ],
                ],
            ]),
            'id'   => '123456',
        ]));

        $headers = $conn->processAction($dto)->getHeaders();
        $this->assertEquals([
            'pf-result-code'   => 1004,
            'pf-cm-event-type' => 'eventUnsubscribe',
        ], $headers);
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
                    'notification_type' => 'access_expiration',
                    'guid'              => 'User',
                    'token'             => 'Token',
                    'system_key'        => 'shopify',
                    'system_name'       => 'Shopify',
                ], $content);
            });

        return $logger;
    }

}