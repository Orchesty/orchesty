<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector\SalesforceCreateContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
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
 * Class SalesforceCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData(Json::encode([
                'email'     => 'email@example.com',
                'firstName' => 'First Name',
                'lastName'  => 'Last Name',
            ]))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'attributes'             => [
                'type' => 'Contact',
                'url'  => '/services/data/v40.0/sobjects/Contact/0031I0000044AJ4QAM',
            ],
            'Id'                     => '0031I0000044AJ4QAM',
            'IsDeleted'              => FALSE,
            'MasterRecordId'         => NULL,
            'AccountId'              => NULL,
            'LastName'               => 'A',
            'FirstName'              => 'asdadadasd',
            'Salutation'             => NULL,
            'Name'                   => 'asdadadasd A',
            'OtherStreet'            => NULL,
            'OtherCity'              => NULL,
            'OtherState'             => NULL,
            'OtherPostalCode'        => NULL,
            'OtherCountry'           => NULL,
            'OtherLatitude'          => NULL,
            'OtherLongitude'         => NULL,
            'OtherGeocodeAccuracy'   => NULL,
            'OtherAddress'           => NULL,
            'MailingStreet'          => NULL,
            'MailingCity'            => NULL,
            'MailingState'           => NULL,
            'MailingPostalCode'      => NULL,
            'MailingCountry'         => NULL,
            'MailingLatitude'        => NULL,
            'MailingLongitude'       => NULL,
            'MailingGeocodeAccuracy' => NULL,
            'MailingAddress'         => NULL,
            'Phone'                  => NULL,
            'Fax'                    => NULL,
            'MobilePhone'            => NULL,
            'HomePhone'              => NULL,
            'OtherPhone'             => NULL,
            'AssistantPhone'         => NULL,
            'ReportsToId'            => NULL,
            'Email'                  => 'nevim@nevim.coma',
            'Title'                  => NULL,
            'Department'             => NULL,
            'AssistantName'          => NULL,
            'LeadSource'             => NULL,
            'Birthdate'              => NULL,
            'Description'            => NULL,
            'OwnerId'                => '0051I000000NJavQAG',
            'CreatedDate'            => '2017-10-27T09:13:49.000+0000',
            'CreatedById'            => '0051I000000NJavQAG',
            'LastModifiedDate'       => '2017-10-27T12:40:33.000+0000',
            'LastModifiedById'       => '0051I000000NJavQAG',
            'SystemModstamp'         => '2017-10-27T12:40:33.000+0000',
            'LastActivityDate'       => NULL,
            'LastCURequestDate'      => NULL,
            'LastCUUpdateDate'       => NULL,
            'LastViewedDate'         => '2017-10-27T12:40:33.000+0000',
            'LastReferencedDate'     => '2017-10-27T12:40:33.000+0000',
            'EmailBouncedReason'     => NULL,
            'EmailBouncedDate'       => NULL,
            'IsEmailBounced'         => FALSE,
            'PhotoUrl'               => '/services/images/photo/0031I0000044AJ4QAM',
            'Jigsaw'                 => NULL,
            'JigsawContactId'        => NULL,
            'CleanStatus'            => 'Pending',
            'Level__c'               => NULL,
            'Languages__c'           => NULL,
            'cm_unsubscribe__c'      => NULL,
            'cm_hard_bounce__c'      => NULL,
        ], $result);
    }

    /**
     * @return SalesforceCreateContactConnector
     */
    private function getConnectorMock(): SalesforceCreateContactConnector
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
                    new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('SalesforceSingleContactItem.json'), []);
            }));

        return new SalesforceCreateContactConnector($this->getSystemMock(), $documentManager, $curlManager);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|SalesforceSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('POST', new Uri('https://na73.salesforce.com')))->setHeaders([
            'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQCPw1z3hchprOA9t08aqFqrY4RdcjNaEmRBHf170davnludWxhbo4WjBgWptw9OSk1yi1c4lfZm5RVo8h9sNsoGEysPd',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        $system = $this->createMock(SalesforceSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}