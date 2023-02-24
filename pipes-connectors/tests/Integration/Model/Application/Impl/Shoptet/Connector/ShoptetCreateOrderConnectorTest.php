<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockServer\Mock;
use HbPFConnectorsTests\MockServer\MockServer;

/**
 * Class ShoptetCreateOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetCreateOrderConnectorTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    private const ID     = 'id';
    private const TYPE   = 'type';
    private const USER   = 'user';
    private const SENDER = 'sender';

    private const HEADERS = [
        self::USER    => self::USER,
        self::TYPE    => 'cancelled',
        'application' => ShoptetApplication::SHOPTET_KEY,
        'internal-id' => '1',
    ];

    private const SETTINGS = [
        'form'           => [
            'cancelled' => -1,
        ],
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
     * @var ShoptetCreateOrderConnector
     */
    private ShoptetCreateOrderConnector $connector;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('shoptet-create-order', $this->connector->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::getApplicationInstall
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessAction(): void
    {
        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    File::getContent(__DIR__ . '/data/ShoptetImportResponse.json'),
                    'POST https://api.myshoptet.com/api/orders',
                ),
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

        $dto = $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                self::HEADERS + [
                    self::ID => $applicationInstall->getId(),
                ],
            ),
        );

        self::assertEquals('', Json::decode($dto->getData())['errors']);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessActionMissingHeader(): void
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

        self::assertException(
            ConnectorException::class,
            NULL,
            "Connector 'shoptet-create-order': invalid-token: Invalid access token.",
        );

        $this->connector->processAction($this->prepareProcessDto([], [self::USER => self::USER]));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::getApplicationInstall
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessActionMissingApplicationInstall(): void
    {

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[]'),
            ),
        );
        self::assertException(
            ApplicationInstallException::class,
            ApplicationInstallException::APP_WAS_NOT_FOUND,
            'Application [shoptet] was not found .',
        );

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::ID   => 'Unknown',
                    self::USER => self::USER,
                    self::TYPE => 'Type',
                ],
            ),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessActionMissingRequest(): void
    {
        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-create-order': %s: Something gone wrong!", CurlException::class),
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender($this->prepareSenderErrorResponse()),
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

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::USER => self::USER,
                    self::ID   => $applicationInstall->getId(),
                ],
            ),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessActionMissingResponse(): void
    {
        self::assertException(
            ConnectorException::class,
            NULL,
            "Connector 'shoptet-create-order': ERROR: Something gone wrong!",
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

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::TYPE => 'Type',
                    self::USER => self::USER,
                    self::ID   => $applicationInstall->getId(),
                ],
            ),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessActionMissingRepeatResponse(): void
    {
        self::assertException(
            OnRepeatException::class,
            0,
            sprintf(
                "Connector 'shoptet-create-order': %s: Connector 'shoptet-create-order': ERROR: Something gone wrong!",
                ConnectorException::class,
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

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::ID   => $applicationInstall->getId(),
                    self::USER => self::USER,
                ],
            ),
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        {

        }
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->connector = self::getContainer()->get('hbpf.connector.shoptet-create-order');
    }

}
