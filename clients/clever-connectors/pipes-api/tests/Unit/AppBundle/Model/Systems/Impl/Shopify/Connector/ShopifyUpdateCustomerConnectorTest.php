<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector\ShopifyUpdateCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
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
            $this->mockCurl()
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
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'system_url'   => 'hbpf',
            'access_token' => 'asd',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) {
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
            }));

        return $curl;
    }

}