<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector\ShopifyCreateCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
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
            $this->mockCurl()
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
     * @return DocumentManager|MockObject
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
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) {
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
            }));

        return $curl;
    }

}