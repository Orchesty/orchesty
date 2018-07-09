<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCheckAdConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
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
 * Class FacebookaudienceCheckAdConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCheckAdConnectorTest extends KernelTestCaseAbstract
{

    private const ACCESS = 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD';

    /**
     * @covers FacebookaudienceCheckAdConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcess(): void
    {
        $data = [
            'client_id' => 'cli',
            'id'        => 'db_id',
            'ref_id'    => 'ad_id',
        ];

        $clb = function (RequestDto $dto, array $options): ResponseDto {
            $expt = new RequestDto('POST',
                new Uri(sprintf('https://graph.facebook.com/v2.12/ad_id')));
            $expt->setHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->setBody(json_encode([
                'fields'       => 'status',
                'access_token' => self::ACCESS,
            ]));

            self::assertEquals($expt, $dto);

            return new ResponseDto(200, '', '{"status": "PAUSED"}', []);
        };

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector($clb);
        $res  = $conn->processAction($dto);

        $body = json_decode($res->getData(), TRUE);
        self::assertArrayHasKey('status', $body);
        self::assertEquals('PAUSED', $body['status']);
        self::assertArrayHasKey('id', $body);
        self::assertEquals('db_id', $body['id']);
    }

    /**
     * @param callable $callback
     *
     * @return FacebookaudienceCheckAdConnector
     * @throws Exception
     */
    private function createConnector(callable $callback): FacebookaudienceCheckAdConnector
    {
        /** @var FacebookaudienceSystem $sys */
        $sys = $this->ownContainer->get('systems.facebookaudience');

        $sysInst = new SystemInstall();
        $sysInst->setSettings([
            OAuth2Provider::ACCESS_TOKEN => self::ACCESS,
        ])
            ->setToken('tkn')
            ->setUser('123')
            ->setSystem('facebookaudience');

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sysInst);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        /** @var CurlManager|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManager::class);
        $curl->method('send')->willReturnCallback($callback);

        return new FacebookaudienceCheckAdConnector($sys, $dm, $curl);
    }

}