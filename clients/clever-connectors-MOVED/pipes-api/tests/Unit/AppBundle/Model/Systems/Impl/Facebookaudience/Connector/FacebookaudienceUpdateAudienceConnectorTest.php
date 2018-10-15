<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\AdTypeEnum;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceUpdateAudienceConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\AudienceMirrorRepository;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class FacebookaudienceUpdateAudienceConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceUpdateAudienceConnectorTest extends KernelTestCaseAbstract
{

    private const ACC    = '103654000491411';
    private const ACCESS = 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD';

    /**
     * @covers FacebookaudienceImageUploadConnector::processAction()
     *
     * @throws Exception
     */
    public function testConnector(): void
    {
        $data = [
            'create'    => [
                'eml1_hash',
                'eml2_hash',
            ],
            'pass_data' => [
                'audience'             => [
                    'name' => 'namae',
                    'id'   => 'someId',
                ],
                'client_id'            => 'cli',
                'audience_description' => 'desc',
                'audience_id'          => NULL,
                'type'                 => AdTypeEnum::FB,
            ],
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector();
        $res  = $conn->processAction($dto);

        $body = json_decode($res->getData(), TRUE);
        self::assertArrayHasKey('audience_id', $body[Comparator::KEY_PASS_DATA]);
        self::assertEquals('audId', $body[Comparator::KEY_PASS_DATA]['audience_id']);
    }

    /**
     * @return FacebookaudienceUpdateAudienceConnector
     * @throws Exception
     */
    private function createConnector(): FacebookaudienceUpdateAudienceConnector
    {
        /** @var FacebookaudienceSystem $sys */
        $sys = $this->ownContainer->get('systems.facebookaudience');

        $sysInst = new SystemInstall();
        $sysInst->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => self::ACCESS,
            FacebookaudienceSystem::AD_ACCOUNT => self::ACC,
        ])
            ->setToken('tkn')
            ->setUser('123')
            ->setSystem('facebookaudience');

        $mirr = new AudienceMirror();

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sysInst);

        /** @var AudienceMirrorRepository|PHPUnit_Framework_MockObject_MockObject $repo2 */
        $repo2 = $this->createMock(AudienceMirrorRepository::class);
        $repo2->method('getByAudience')->willReturn($mirr);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($repo);
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($repo2);

        /** @var CurlManager|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManager::class);
        $curl->expects($this->at(0))
            ->method('send')->willReturnCallback(
                function (RequestDto $dto, array $options): ResponseDto {
                    $expt = new RequestDto('POST',
                        new Uri(sprintf('https://graph.facebook.com/v2.12/act_%s/customaudiences', self::ACC)));
                    $expt->setHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'multipart/form-data',
                    ]);

                    self::assertEquals($expt, $dto);
                    self::assertEquals([
                        'access_token' => self::ACCESS,
                        'name'         => 'namae',
                        'description'  => 'desc',
                        'subtype'      => 'CUSTOM',
                    ], $options['form_params']);

                    return new ResponseDto(200, '', '{"id": "audId"}', []);
                }
            );
        $curl->expects($this->at(1))
            ->method('send')->willReturnCallback(
                function (RequestDto $dto, array $options): ResponseDto {
                    $expt = new RequestDto('POST',
                        new Uri(sprintf('https://graph.facebook.com/v2.12/audId/users', self::ACC)));
                    $expt->setHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'multipart/form-data',
                    ]);

                    self::assertEquals($expt, $dto);
                    self::assertEquals([
                        'access_token' => self::ACCESS,
                        'payload'      => [
                            'schema' => 'EMAIL_SHA256',
                            'data'   => ['eml1_hash', 'eml2_hash'],
                        ],
                    ], $options['form_params']);

                    return new ResponseDto(200, '', '{"invalid_entry_samples": {}}', []);
                }
            );

        return new FacebookaudienceUpdateAudienceConnector($sys, $dm, $curl);
    }

}