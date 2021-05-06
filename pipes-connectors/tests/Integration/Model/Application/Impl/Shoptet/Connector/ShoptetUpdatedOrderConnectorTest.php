<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class ShoptetUpdatedOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetUpdatedOrderConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const USER    = 'user';
    private const SENDER  = 'sender';
    private const HEADERS = [
        'pf-user'        => self::USER,
        'pf-application' => ShoptetApplication::SHOPTET_KEY,
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
     * @var ShoptetUpdatedOrderConnector
     */
    private ShoptetUpdatedOrderConnector $connector;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('shoptet-updated-order-connector', $this->connector->getId());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector::processEvent
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     *
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS,
        );
        $this->pfd($applicationInstall);

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

        $dto = $this->connector->processEvent(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );

        self::assertEquals('{"externalCode":"1","status":{"id":"-1"}}', $dto->getData());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processEvent
     *
     * @throws Exception
     */
    public function testProcessEventMissingEventInstance(): void
    {
        $applicationInstall = DataProvider::createApplicationInstall(ShoptetApplication::SHOPTET_KEY, self::USER);
        $this->pfd($applicationInstall);

        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-updated-order-connector': Content 'eventInstance' does not exist!",
        );
        $this->connector->processEvent($this->prepareProcessDto(['eshopId' => 'user'], self::HEADERS));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector::processEvent
     *
     * @throws Exception
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
        $this->pfd($applicationInstall);

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender($this->prepareSenderErrorResponse()),
        );

        $this->connector->processEvent(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector::processEvent
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     *
     * @throws Exception
     */
    public function testProcessEventMissingResponse(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-updated-order-connector': ERROR: Something gone wrong!",
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS,
        );
        $this->pfd($applicationInstall);

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    '{"errors":[{"errorCode":"ERROR","instance":"Instance","message":"Something gone wrong!"}],"data":null}',
                ),
            ),
        );

        $this->connector->processEvent(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector::processEvent
     *
     * @throws Exception
     */
    public function testProcessEventMissingRepeatResponse(): void
    {
        self::assertException(
            OnRepeatException::class,
            ProcessDto::DO_NOT_CONTINUE,
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
        $this->pfd($applicationInstall);

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    '{"errors":[{"errorCode":"ERROR","instance":"url-locked","message":"Something gone wrong!"}],"data":null}',
                ),
            ),
        );

        $this->connector->processEvent(
            $this->prepareProcessDto('{"eventInstance":"1", "eshopId": "user"}', self::HEADERS),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdatedOrderConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION,
            sprintf('Method %s::processAction is not supported!', ShoptetUpdatedOrderConnector::class),
        );

        $this->connector->processAction($this->prepareProcessDto());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.shoptet-updated-order-connector');
    }

}
