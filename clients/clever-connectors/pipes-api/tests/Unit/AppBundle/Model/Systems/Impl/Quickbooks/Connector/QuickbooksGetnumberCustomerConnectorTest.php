<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksGetnumberCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper\QuickbooksCreateCustomerMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class QuickbooksGetnumberCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
final class QuickbooksGetnumberCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessConnector(): void
    {
        $dto = new ProcessDto();
        $dto->setHeaders([])
            ->setData(json_encode([
                QuickbooksCreateCustomerMapper::SUCCESS => FALSE,
                QuickbooksCreateCustomerMapper::ATTEMPT => TRUE,
                'body'                                  => json_encode([
                    'PrimaryEmailAddr'                         => [
                        'Address' => 'eml@eml.com',
                    ],
                    QuickbooksCreateCustomerMapper::FIRST_NAME => 'nao',
                    QuickbooksCreateCustomerMapper::LAST_NAME  => 'namae',
                ]),
            ]));

        $conn = new QuickbooksGetnumberCustomerConnector(
            $this->mockDm(),
            $this->container->get('systems.quickbooks'),
            $this->mockCurl()
        );

        $res = $conn->processAction($dto);

        self::assertEquals(json_encode([
            QuickbooksCreateCustomerMapper::SUCCESS => FALSE,
            QuickbooksCreateCustomerMapper::ATTEMPT => TRUE,
            'body'                                  => json_encode([
                'PrimaryEmailAddr'                         => [
                    'Address' => 'eml@eml.com',
                ],
                QuickbooksCreateCustomerMapper::FIRST_NAME => 'nao',
                QuickbooksCreateCustomerMapper::LAST_NAME  => 'namae#3',
            ]),
        ]), $res->getData());
    }

    /**
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
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
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $expt = new RequestDto(CurlManager::METHOD_GET,
                        new Uri('https://sandbox-quickbooks.api.intuit.com/v3/company/realm/query?query=SELECT+COUNT%28%2A%29+FROM+CUSTOMER+WHERE+Active+IN+%28true%2C+false%29+AND+GivenName%3D\'nao\'+AND+FamilyName+LIKE+\'namae%23%25\'')
                    );
                    $expt->setHeaders([
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer token',
                    ]);

                    self::assertEquals($expt, $requestDto);

                    return new ResponseDto(200, '', $this->getRequest('QueryForNameCount.json'), []);
                }
            ));

        return $curl;
    }

}