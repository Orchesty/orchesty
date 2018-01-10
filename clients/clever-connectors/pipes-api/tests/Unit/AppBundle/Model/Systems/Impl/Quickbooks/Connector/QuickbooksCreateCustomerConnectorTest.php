<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksCreateCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper\QuickbooksCreateCustomerMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'access_token' => 'token',
            'realmId'      => 'realm',
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
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
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

                    return new ResponseDto(200, '', '', []);
                }
            ));

        return $curl;
    }

}