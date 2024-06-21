<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class ShoptetUpdatedOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector
 */
#[CoversClass(ShoptetUpdatedOrderConnector::class)]
#[CoversClass(ShoptetConnectorAbstract::class)]
final class ShoptetUpdatedOrderConnectorTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    private const USER    = 'user';
    private const SENDER  = 'sender';
    private const HEADERS = [
        'application' => ShoptetApplication::SHOPTET_KEY,
        'user'        => self::USER,
    ];

    private const SETTINGS = [
        'clientSettings' => [
            'token' => [
                'access_token' => 'Access Token',
                'expires_in'   => '2147483647',
            ],
        ],
    ];

    private const NON_ENCRYPTED_SETTINGS = [
        'getApiKey' => [
            'receivingStatus' => 'unlock',
        ],
    ];

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @var ShoptetUpdatedOrderConnector
     */
    private ShoptetUpdatedOrderConnector $connector;

    /**
     * @return void
     */
    public function testGetName(): void
    {
        self::assertEquals('shoptet-updated-order-connector', $this->connector->getName());
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS,
        );

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    '{"data":{"order":{"externalCode":"1","status":{"id":"-1"}}}}',
                    'GET https://api.myshoptet.com/api/orders/1?include=notes',
                ),
            ),
        );

        $dto = $this->connector->processAction(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );

        self::assertEquals('{"externalCode":"1","status":{"id":"-1"}}', $dto->getData());
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessEventMissingRequest(): void
    {
        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-updated-order-connector': %s: Something gone wrong!", CurlException::class),
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS,
        );

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender($this->prepareSenderErrorResponse()),
        );

        $this->connector->processAction(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );
    }

    /**
     * @throws Exception
     */
    public function testProcessEventMissingResponse(): void
    {
        self::assertException(
            ConnectorException::class,
            NULL,
            "Connector 'shoptet-updated-order-connector': ERROR: Something gone wrong!",
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS,
        );

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    '{"errors":[{"errorCode":"ERROR","instance":"Instance","message":"Something gone wrong!"}],"data":null}',
                ),
            ),
        );

        $this->connector->processAction(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessEventMissingRepeatResponse(): void
    {
        self::assertException(
            OnRepeatException::class,
            0,
            sprintf(
                "Connector 'shoptet-updated-order-connector': %s: Connector 'shoptet-updated-order-connector': ERROR: Something gone wrong!",
                ConnectorException::class,
            ),
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS,
        );

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    '{"errors":[{"errorCode":"ERROR","instance":"url-locked","message":"Something gone wrong!"}],"data":null}',
                ),
            ),
        );

        $this->connector->processAction(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->connector = self::getContainer()->get('hbpf.connector.shoptet-updated-order-connector');
    }

}
