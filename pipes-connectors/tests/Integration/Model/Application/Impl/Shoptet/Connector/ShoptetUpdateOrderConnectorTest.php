<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class ShoptetUpdateOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetUpdateOrderConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const ID          = 'pf-id';
    private const TYPE        = 'pf-type';
    private const EXTERNAL_ID = 'pf-external-id';
    private const USER        = 'user';
    private const SENDER      = 'sender';

    private const HEADERS = [
        'pf-user'         => self::USER,
        self::TYPE        => 'cancelled',
        'pf-application'  => ShoptetApplication::SHOPTET_KEY,
        'pf-internal-id'  => '1',
        self::EXTERNAL_ID => '1',
    ];

    private const SETTINGS = [
        'form'           => [
            'cancelled'                  => -1,
            ShoptetApplication::ESHOP_ID => 125,
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
     * @var ShoptetUpdateOrderConnector
     */
    private $connector;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('shoptet-update-order', $this->connector->getId());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    (string) file_get_contents(__DIR__ . '/data/ShoptetUpdateResponse.json'),
                    'PATCH https://api.myshoptet.com/api/orders/125/status?suppressDocumentGeneration=true&suppressEmailSending=true&suppressSmsSending=true'
                )
            )
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS
        );
        $this->pfd($applicationInstall);
        $this->dm->refresh($applicationInstall);

        $dto = $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                self::HEADERS + [
                    self::ID => $applicationInstall->getId(),
                ]
            )
        );
        $this->dm->clear();

        self::assertEquals('', Json::decode($dto->getData())['errors']);
        self::assertArrayHasKey(PipesHeaders::createKey('user'), $dto->getHeaders());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     * @throws Exception
     */
    public function testProcessActionMissingHeader(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-update-order': Header 'id' does not exist!"
        );

        $this->connector->processAction($this->prepareProcessDto([], [self::TYPE => 'Type']));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::getApplicationInstall
     *
     * @throws Exception
     */
    public function testProcessActionMissingApplicationInstall(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-update-order': ApplicationInstall with key 'Unknown' does not exist!"
        );

        $this->connector->processAction($this->prepareProcessDto([], [self::ID => 'Unknown', self::TYPE => 'Type']));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionMissingRequest(): void
    {
        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-update-order': %s: Something gone wrong!", CurlException::class)
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender($this->prepareSenderErrorResponse())
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS
        );
        $this->pfd($applicationInstall);
        $this->dm->refresh($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::EXTERNAL_ID => '1',
                    self::ID          => $applicationInstall->getId(),
                ]
            )
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     * @throws Exception
     */
    public function testProcessActionMissingResponse(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-update-order': ERROR: Something gone wrong!"
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    '{"errors":[{"errorCode":"ERROR","instance":"Instance","message":"Something gone wrong!"}],"data":null}'
                )
            )
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS
        );
        $this->pfd($applicationInstall);
        $this->dm->refresh($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::EXTERNAL_ID => '1',
                    self::TYPE        => 'Type',
                    self::ID          => $applicationInstall->getId(),
                ]
            )
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     *
     * @throws Exception
     */
    public function testProcessActionMissingRepeatResponse(): void
    {
        self::assertException(
            OnRepeatException::class,
            ProcessDto::DO_NOT_CONTINUE,
            sprintf(
                "Connector 'shoptet-update-order': %s: Connector 'shoptet-update-order': ERROR: Something gone wrong!",
                ConnectorException::class
            )
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderResponse(
                    '{"errors":[{"errorCode":"ERROR","instance":"url-locked","message":"Something gone wrong!"}],"data":null}'
                )
            )
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS
        );
        $this->pfd($applicationInstall);
        $this->dm->refresh($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::EXTERNAL_ID => '1',
                    self::ID          => $applicationInstall->getId(),
                ]
            )
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processEvent
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT,
            sprintf('Method %s::processEvent is not supported!', ShoptetUpdateOrderConnector::class)
        );

        $this->connector->processEvent($this->prepareProcessDto());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.shoptet-update-order');
    }

}
