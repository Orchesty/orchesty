<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector\BigcommerceCreateCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
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
        $conn = $this->getConnectorMock();

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            "email"      => "eml@eml.com",
            "first_name" => "qwe",
            "last_name"  => "asd",
        ]))->setHeaders([]);

        $conn->processAction($dto);
    }

    /**
     * @return BigcommerceCreateCustomerConnector
     */
    private function getConnectorMock(): BigcommerceCreateCustomerConnector
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'access_token' => 'smToken',
            'client_id'    => 'clientId',
            'store_id'     => 'storeId',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var PHPUnit_Framework_MockObject_MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($repo);

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $dto) {
                $expt = new RequestDto(CurlManager::METHOD_POST,
                    new Uri('https://api.bigcommerce.com/stores/storeId/v2/customers'));
                $expt->setHeaders([
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'X-Auth-Client' => 'clientId',
                    'X-Auth-Token'  => 'smToken',
                ])->setBody(json_encode([
                    "email"      => "eml@eml.com",
                    "first_name" => "qwe",
                    "last_name"  => "asd",
                ]));

                $this->assertEquals($expt, $dto);

                return new ResponseDto(201, '', '', []);
            }));

        return new BigcommerceCreateCustomerConnector($this->container->get('systems.bigcommerce'), $documentManager,
            $curlManager);
    }

}