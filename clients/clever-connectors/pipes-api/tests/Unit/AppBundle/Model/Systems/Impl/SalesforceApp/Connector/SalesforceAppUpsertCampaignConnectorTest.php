<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.4.18
 * Time: 18:50
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\CustomFieldsConnector\CMGetCustomFieldsConnector;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAppUpsertCampaignConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAuthConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class SalesforceAppUpsertCampaignConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
final class SalesforceAppUpsertCampaignConnectorTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $dto  = $this->getDto();
        $conn = $this->getConnector();

        $this->expectException(ConnectorException::class);
        $this->expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
        $conn->processEvent($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $dto  = $this->getDto();
        $conn = $this->getConnector();

        $conn->processAction($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionFailed(): void
    {
        $dto  = $this->getDto();
        $conn = $this->getConnector(TRUE);

        $this->expectException(ConnectorException::class);
        $conn->processAction($dto);
    }

    /**
     * --------------------------------------------- HELPERS -------------------------------------------
     */

    /**
     * @param bool $withCurlError
     *
     * @return SalesforceAppUpsertCampaignConnector
     * @throws Exception
     */
    private function getConnector(bool $withCurlError = FALSE): SalesforceAppUpsertCampaignConnector
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('usr')
            ->setToken('tok')
            ->setSettings(
                [
                    OAuth2Provider::ACCESS_TOKEN => 'abcd123',
                    SalesforceAppSystem::API_URL => 'https://example.com',
                ]
            );

        /** @var OAuth2Provider|MockObject $provider */
        $provider = $this->createMock(OAuth2Provider::class);

        /** @var CurlManager|MockObject $curl */
        $curl = $this->createMock(CurlManager::class);
        if ($withCurlError) {
            $curl->method('send')->willThrowException(new CurlException(''));
        }

        /** @var StartingPointHandler|MockObject $handler */
        $handler = $this->createMock(StartingPointHandler::class);

        /** @var SystemLimitManager|MockObject $limit */
        $limit = $this->createMock(SystemLimitManager::class);

        $conn  = new SalesforceAuthConnector($curl);
        $conn2 = new CMGetCustomFieldsConnector($curl);
        $sys   = new SalesforceAppSystem($provider, $conn, $handler, $limit, $conn2);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var DocumentManager|MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return new SalesforceAppUpsertCampaignConnector($sys, $curl, $dm);
    }

    /**
     * @return ProcessDto
     */
    private function getDto(): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setData('{}')
            ->setHeaders([]);

        return $dto;
    }

}