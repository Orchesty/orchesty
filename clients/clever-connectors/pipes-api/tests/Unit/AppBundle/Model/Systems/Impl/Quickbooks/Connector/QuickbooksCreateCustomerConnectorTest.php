<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksCreateCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper\QuickbooksCreateCustomerMapper;
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
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class QuickbooksCreateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
final class QuickbooksCreateCustomerConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new QuickbooksCreateCustomerConnector(
            $this->mockDm(),
            $this->container->get('systems.quickbooks'),
            $this->mockCurl()
        );

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            QuickbooksCreateCustomerMapper::SUCCESS => FALSE,
            QuickbooksCreateCustomerMapper::ATTEMPT => TRUE,
            'body'                                  => json_encode([
                'PrimaryEmailAddr' => [
                    'Address' => 'eml',
                ],
                'GivenName'        => 'namae',
                'FamilyName'       => 'last',
            ]),
        ]))->setHeaders([]);

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testConnectorFirstPass(): void
    {
        $conn = new QuickbooksCreateCustomerConnector(
            $this->mockDm(),
            $this->container->get('systems.quickbooks'),
            $this->mockCurl(400)
        );

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            QuickbooksCreateCustomerMapper::SUCCESS => FALSE,
            QuickbooksCreateCustomerMapper::ATTEMPT => FALSE,
            'body'                                  => json_encode([
                'PrimaryEmailAddr' => [
                    'Address' => 'eml',
                ],
                'GivenName'        => 'namae',
                'FamilyName'       => 'last',
            ]),
        ]))->setHeaders([]);

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testConnectorLimit(): void
    {
        $conn = new QuickbooksCreateCustomerConnector(
            $this->mockDm(),
            $this->container->get('systems.quickbooks'),
            $this->mockCurl(429)
        );

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            QuickbooksCreateCustomerMapper::SUCCESS => FALSE,
            QuickbooksCreateCustomerMapper::ATTEMPT => TRUE,
            'body'                                  => json_encode([
                'PrimaryEmailAddr' => [
                    'Address' => 'eml',
                ],
                'GivenName'        => 'namae',
                'FamilyName'       => 'last',
            ]),
        ]))->setHeaders([]);

        $res = $conn->processAction($dto);
        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

    /**
     *
     */
    public function testConnectorError(): void
    {
        $conn = new QuickbooksCreateCustomerConnector(
            $this->mockDm(),
            $this->container->get('systems.quickbooks'),
            $this->mockCurl(400)
        );

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            QuickbooksCreateCustomerMapper::SUCCESS => FALSE,
            QuickbooksCreateCustomerMapper::ATTEMPT => TRUE,
            'body'                                  => json_encode([
                'PrimaryEmailAddr' => [
                    'Address' => 'eml',
                ],
                'GivenName'        => 'namae',
                'FamilyName'       => 'last',
            ]),
        ]))->setHeaders([]);

        $conn->setLogger($this->mockLogger());
        $this->expectException(CurlException::class);
        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'access_token' => 'token',
            'realmId'      => 'realm',
        ])->setUser('user')->setToken('tkn');

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @param int $status
     *
     * @return CurlManagerInterface
     */
    private function mockCurl(int $status = 200): CurlManagerInterface
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) use ($status) {
                    $expt = new RequestDto(CurlManager::METHOD_POST,
                        new Uri('https://sandbox-quickbooks.api.intuit.com/v3/company/realm/customer'));
                    $expt->setHeaders([
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer token',
                    ])->setBody(json_encode([
                        'PrimaryEmailAddr' => [
                            'Address' => 'eml',
                        ],
                        'GivenName'        => 'namae',
                        'FamilyName'       => 'last',
                    ]));

                    self::assertEquals($expt, $requestDto);

                    if ($status >= 300) {
                        throw new CurlException('', 0, NULL, new Response($status));
                    }

                    return new ResponseDto($status, '', '', []);
                }
            ));

        return $curl;
    }

    /**
     * @return LoggerInterface
     */
    private function mockLogger(): LoggerInterface
    {
        /** @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')->will($this->returnCallback(
                function (string $type, $data): void {
                    self::assertEquals('data_error', $type);
                    self::assertEquals([
                        'guid'        => 'user',
                        'token'       => 'tkn',
                        'system_key'  => 'quickbooks',
                        'system_name' => 'Quickbooks',
                    ], $data);
                }
            ));

        return $logger;
    }

}