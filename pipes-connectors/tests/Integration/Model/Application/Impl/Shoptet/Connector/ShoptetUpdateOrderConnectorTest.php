<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class ShoptetUpdateOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector
 */
#[CoversClass(ShoptetUpdateOrderConnector::class)]
#[CoversClass(ShoptetConnectorAbstract::class)]
final class ShoptetUpdateOrderConnectorTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    private const ID          = 'id';
    private const TYPE        = 'type';
    private const EXTERNAL_ID = 'external-id';
    private const USER        = 'user';
    private const SENDER      = 'sender';

    private const HEADERS = [
        'application'     => ShoptetApplication::SHOPTET_KEY,
        'internal-id'     => '1',
        'user'            => self::USER,
        self::EXTERNAL_ID => '1',
        self::TYPE        => 'cancelled',
    ];

    private const SETTINGS = [
        'clientSettings'                         => [
            'token' => [
                'access_token' => 'Access Token',
                'expires_in'   => '2147483647',
            ],
        ],
        ApplicationInterface::AUTHORIZATION_FORM => [
            'cancelled'                  => -1,
            ShoptetApplication::ESHOP_ID => 125,
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
     * @var ShoptetUpdateOrderConnector
     */
    private ShoptetUpdateOrderConnector $connector;

    /**
     * @return void
     */
    public function testGetName(): void
    {
        self::assertEquals('shoptet-update-order', $this->connector->getName());
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    File::getContent(__DIR__ . '/data/ShoptetUpdateResponse.json'),
                    'PATCH https://api.myshoptet.com/api/orders/125/status?suppressDocumentGeneration=true&suppressEmailSending=true&suppressSmsSending=true',
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
        self::assertArrayHasKey('user', $dto->getHeaders());
    }

    /**
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
            "Connector 'shoptet-update-order': invalid-token: Invalid access token.",
        );

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::TYPE => 'Type',
                    self::USER => self::USER,
                ],
            ),
        );
    }

    /**
     * @throws Exception
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
                    self::TYPE => 'Type',
                    self::USER => self::USER,
                ],
            ),
        );
    }

    /**
     * @throws Exception
     */
    public function testProcessActionMissingRequest(): void
    {
        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-update-order': %s: Something gone wrong!", CurlException::class),
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
                    self::EXTERNAL_ID => '1',
                    self::ID          => $applicationInstall->getId(),
                    self::USER        => self::USER,
                ],
            ),
        );
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws CustomNodeException
     * @throws PipesFrameworkException
     * @throws Exception
     */
    public function testProcessActionMissingResponse(): void
    {
        self::assertException(
            ConnectorException::class,
            NULL,
            "Connector 'shoptet-update-order': ERROR: Something gone wrong!",
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
                    self::EXTERNAL_ID => '1',
                    self::ID          => $applicationInstall->getId(),
                    self::TYPE        => 'Type',
                    self::USER        => self::USER,
                ],
            ),
        );
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessActionMissingRepeatResponse(): void
    {
        self::assertException(
            OnRepeatException::class,
            0,
            sprintf(
                "Connector 'shoptet-update-order': %s: Connector 'shoptet-update-order': ERROR: Something gone wrong!",
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
                    self::EXTERNAL_ID => '1',
                    self::ID          => $applicationInstall->getId(),
                    self::USER        => self::USER,
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

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->connector = self::getContainer()->get('hbpf.connector.shoptet-update-order');
    }

}
