<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector\BigcommerceGetCustomerConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceGetCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceGetCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData('{"id":1}')->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'id'                      => 1,
            'company'                 => '',
            'first_name'              => 'User01',
            'last_name'               => 'User01',
            'email'                   => 'User01@User01.com',
            'phone'                   => '',
            'form_fields'             => NULL,
            'date_created'            => 'Tue, 17 Oct 2017 10:42:48 +0000',
            'date_modified'           => 'Tue, 17 Oct 2017 10:42:48 +0000',
            'store_credit'            => '0.0000',
            'registration_ip_address' => '188.122.212.69',
            'customer_group_id'       => 0,
            'notes'                   => '',
            'tax_exempt_category'     => '',
            'reset_pass_on_login'     => FALSE,
            'accepts_marketing'       => TRUE,
            'addresses'               => [
                'url'      => 'https://api.bigcommerce.com/stores/ukcfcghi/v2/customers/1/addresses',
                'resource' => '/customers/1/addresses',
            ],
        ], $result);
    }

    /**
     * @return BigcommerceGetCustomerConnector
     */
    private function getConnectorMock(): BigcommerceGetCustomerConnector
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var PHPUnit_Framework_MockObject_MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                $this->assertEquals(
                    new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/customers/1'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('BigcommerceSingleCustomerItem.json'), []);
            }));

        return new BigcommerceGetCustomerConnector($this->getSystemMock(), $documentManager, $curlManager);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|BigcommerceSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('GET', new Uri('https://api.bigcommerce.com/stores/ukcfcghi/v2/')))->setHeaders([
            'X-Auth-Client' => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
            'X-Auth-Token'  => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        $system = $this->createMock(BigcommerceSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}