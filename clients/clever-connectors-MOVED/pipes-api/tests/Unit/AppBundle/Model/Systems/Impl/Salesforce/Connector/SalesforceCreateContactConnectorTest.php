<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector\SalesforceCreateContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
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
        $result = Json::decode($this->getConnectorMock(function (RequestDto $dto, array $options = []) {
            $this->assertEquals(
                new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact'),
                $dto->getUri()
            );

            return new ResponseDto(200, 'OK', $this->getRequest('SalesforceSingleContactItem.json'), []);
        })->processAction(
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
     *
     */
    public function testProcessActionWithLimiter(): void
    {
        $headers = $this->getConnectorMock(function (RequestDto $dto, array $options = []): void {
            $this->assertEquals(
                new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact'),
                $dto->getUri()
            );

            throw new CurlException('', CurlException::REQUEST_FAILED, NULL,
                new Response(403, [], '"errorCode":"REQUEST_LIMIT_EXCEEDED"')
            );
        })->processAction((new ProcessDto())->setData(Json::encode([
            'email'     => 'email@example.com',
            'firstName' => 'First Name',
            'lastName'  => 'Last Name',
        ]))->setHeaders([]))->getHeaders();

        $this->assertEquals(['pf-result-code' => 1004], $headers);
    }

    /**
     *
     */
    public function testProcessActionWithException(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::REQUEST_FAILED);

        $this->getConnectorMock(
            function (RequestDto $dto, array $options = []): void {
                $this->assertEquals(
                    new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact'),
                    $dto->getUri()
                );

                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(401));
            },
            function (string $type, array $content): void {
                $this->assertEquals('access_expiration', $type);
                $this->assertEquals([
                    'notification_type' => 'access_expiration',
                    'guid'              => 'User',
                    'token'             => 'Token',
                    'system_key'        => 'salesforce',
                    'system_name'       => 'Salesforce',
                ], $content);
            }
        )->processAction((new ProcessDto())->setData(Json::encode([
            'email'     => 'email@example.com',
            'firstName' => 'First Name',
            'lastName'  => 'Last Name',
        ]))->setHeaders([]));
    }

    /**
     * @param callable      $curlCallback
     * @param callable|NULL $loggerCallback
     *
     * @return SalesforceCreateContactConnector
     */
    private function getConnectorMock(
        callable $curlCallback,
        ?callable $loggerCallback = NULL
    ): SalesforceCreateContactConnector
    {
        $dto = (new RequestDto('POST', new Uri('https://na73.salesforce.com')))
            ->setHeaders([
                'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQCPw1z3hchprOA9t08aqFqrY4RdcjNaEmRBHf170davnludWxhbo4WjBgWptw9OSk1yi1c4lfZm5RVo8h9sNsoGEysPd',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);

        /** @var SalesforceSystem|MockObject $system */
        $system = $this->createPartialMock(SalesforceSystem::class, ['getRequestDto']);
        $system->method('getRequestDto')->willReturn($dto);

        /** @var SystemInstall|MockObject $systemInstall */
        $systemInstall = $this->createPartialMock(SystemInstallRepository::class, ['getSystemInstallFromHeaders']);
        $systemInstall->method('getSystemInstallFromHeaders')->willReturn(
            (new SystemInstall())
                ->setUser('User')
                ->setToken('Token')
        );

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')->willReturnCallback($curlCallback);

        $connector = new SalesforceCreateContactConnector($system, $documentManager, $curlManager);

        if ($loggerCallback) {
            /** @var LoggerInterface|MockObject $logger */
            $logger = $this->createMock(LoggerInterface::class);
            $logger->method('info')->willReturnCallback($loggerCallback);
            $connector->setLogger($logger);
        }

        return $connector;
    }

}