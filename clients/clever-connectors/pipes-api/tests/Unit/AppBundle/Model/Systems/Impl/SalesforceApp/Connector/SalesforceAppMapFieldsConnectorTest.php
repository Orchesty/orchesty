<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 28.5.18
 * Time: 10:39
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAppMapFieldsConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppMapperAbstract;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class SalesforceAppMapFieldsConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
final class SalesforceAppMapFieldsConnectorTest extends TestCase
{

    /**
     * @var SalesforceAppMapFieldsConnector
     */
    private $connector;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $this->createConnector();

        $this->expectException(ConnectorException::class);
        $this->expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
        $this->connector->processEvent(new ProcessDto());
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $data = [
            [
                SalesforceAppMapperAbstract::CM_FIELD  => 'cm_1',
                SalesforceAppMapperAbstract::ID_CUSTOM => '1',
            ],
            [
                SalesforceAppMapperAbstract::CM_FIELD  => 'cm_2',
                SalesforceAppMapperAbstract::ID_CUSTOM => '2',
            ],
        ];

        $this->createConnector($data);

        $ret = $this->connector->processAction(new ProcessDto());

        self::assertInstanceOf(ProcessDto::class, $ret);
        self::assertNotEmpty($this->systemInstall->getSettings());
        self::assertArrayHasKey('mapFields', $this->systemInstall->getSettings());
        $mf = $this->systemInstall->getSettings()['mapFields'];
        self::assertCount(2, $mf);

        self::assertArrayHasKey(SalesforceAppMapperAbstract::ID_CUSTOM, $mf[0]);
        self::assertArrayHasKey(SalesforceAppMapperAbstract::CM_FIELD, $mf[0]);

        self::assertArrayHasKey(SalesforceAppMapperAbstract::ID_CUSTOM, $mf[1]);
        self::assertArrayHasKey(SalesforceAppMapperAbstract::CM_FIELD, $mf[1]);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionNoData(): void
    {
        $this->createConnector();
        $ret = $this->connector->processAction(new ProcessDto());

        self::assertInstanceOf(ProcessDto::class, $ret);
        self::assertNotEmpty($this->systemInstall->getSettings());
        self::assertArrayHasKey('mapFields', $this->systemInstall->getSettings());
    }

    /**
     * ---------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param array $data
     *
     * @throws Exception
     */
    private function createConnector(array $data = []): void
    {
        $this->systemInstall = new SystemInstall();
        $this->systemInstall
            ->setUser('user123')
            ->setSystem('sys123')
            ->setToken('tok123');

        $reqDto = new RequestDto(CurlManager::METHOD_POST, new Uri('https://salesforce.com'));
        $resDto = new ResponseDto(200, 'OK', json_encode($data), []);

        /** @var CurlManager|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManager::class);
        $curlManager->method('send')->willReturn($resDto);

        /** @var SystemInstallRepository|MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($this->systemInstall);

        /** @var DocumentManager|MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);
        $dm->method('flush')->willReturn(TRUE);

        /** @var SalesforceAppSystem|MockObject $system */
        $system = $this->createMock(SalesforceAppSystem::class);
        $system->method('getRequestDto')->willReturn($reqDto);

        $this->connector = new SalesforceAppMapFieldsConnector($curlManager, $dm, $system);
    }

}