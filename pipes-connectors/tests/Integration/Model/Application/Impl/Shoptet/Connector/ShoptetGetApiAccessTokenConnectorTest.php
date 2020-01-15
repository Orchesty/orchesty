<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetApiAccessTokenConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShoptetGetApiAccessTokenConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetGetApiAccessTokenConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const HEADERS = [
        'pf-user'        => 'user',
        'pf-application' => ShoptetApplication::SHOPTET_KEY,
    ];

    private const API_TOKEN_URL = 'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken';

    /**
     * @var ShoptetGetApiAccessTokenConnector
     */
    private $connector;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.shoptet-get-api-access-token');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetApiAccessTokenConnector::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('shoptet-get-access-token', $this->connector->getId());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetApiAccessTokenConnector::processEvent
     *
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        self::expectException(ConnectorException::class);
        $this->connector->processEvent(new ProcessDto());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetApiAccessTokenConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetApiAccessTokenConnector::processActionArray
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockSender();
        $this->insertApplicationInstall();
        $data = $this->connector->processAction((new ProcessDto())->setHeaders(self::HEADERS));

        self::assertEquals('{"data":"data"}', $data->getData());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetApiAccessTokenConnector::processActionArray
     *
     * @throws Exception
     */
    public function testProcessActionArray(): void
    {
        $this->mockSender();
        $applicationInstall = $this->insertApplicationInstall();

        $data = $this->connector->processActionArray($applicationInstall, new ProcessDto());
        self::assertEquals(['data' => 'data'], $data);
    }

    /**
     * @return ApplicationInstall
     *
     * @throws Exception
     */
    private function insertApplicationInstall(): ApplicationInstall
    {
        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            'user',
            [
                ApplicationInterface::AUTHORIZATION_SETTINGS => [
                    ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '___access_token__'],
                ],
                ApplicationAbstract::FORM                    => [ShoptetApplication::API_TOKEN_URL => self::API_TOKEN_URL],
            ]
        );
        $this->pf($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @throws Exception
     */
    private function mockSender(): void
    {
        $this->setProperty(
            $this->connector,
            'sender',
            $this->prepareSender(
                static fn() => new ResponseDto(
                    200,
                    '',
                    '{"data":"data"}',
                    [
                        'Authorization' => sprintf('Bearer %s', '___access_token__'),
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ]
                )
            )
        );
    }

}
