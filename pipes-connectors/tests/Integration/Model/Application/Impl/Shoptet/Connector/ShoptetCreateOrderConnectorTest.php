<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShoptetCreateOrderConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetCreateOrderConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const ID     = 'pf-id';
    private const TYPE   = 'pf-type';
    private const USER   = 'user';
    private const SENDER = 'sender';

    private const HEADERS = [
        'pf-user'        => self::USER,
        self::TYPE       => 'cancelled',
        'pf-application' => ShoptetApplication::SHOPTET_KEY,
        'pf-internal-id' => '1',
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
    private $connector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.shoptet-create-order');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('shoptet-create-order', $this->connector->getId());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::getApplicationInstall
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
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
                    (string) file_get_contents(__DIR__ . '/data/ShoptetImportResponse.json'),
                    'POST https://api.myshoptet.com/api/orders'
                )
            )
        );

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS
        );
        $this->pf($applicationInstall);

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
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionMissingHeader(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-create-order': Header 'id' does not exist!"
        );

        $this->connector->processAction($this->prepareProcessDto([], [self::TYPE => 'Type']));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::getApplicationInstall
     *
     * @throws Exception
     */
    public function testProcessActionMissingApplicationInstall(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-create-order': ApplicationInstall with key 'Unknown' does not exist!"
        );

        $this->connector->processAction($this->prepareProcessDto([], [self::ID => 'Unknown', self::TYPE => 'Type']));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionMissingRequest(): void
    {
        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-create-order': %s: Something gone wrong!", CurlException::class)
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
        $this->pf($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::ID => $applicationInstall->getId(),
                ]
            )
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionMissingResponse(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 'shoptet-create-order': ERROR: Something gone wrong!"
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
        $this->pf($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::TYPE => 'Type',
                    self::ID   => $applicationInstall->getId(),
                ]
            )
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processAction
     *
     * @throws Exception
     */
    public function testProcessActionMissingRepeatResponse(): void
    {
        self::assertException(
            OnRepeatException::class,
            ProcessDto::DO_NOT_CONTINUE,
            sprintf(
                "Connector 'shoptet-create-order': %s: Connector 'shoptet-create-order': ERROR: Something gone wrong!",
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
        $this->pf($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::ID => $applicationInstall->getId(),
                ]
            )
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetCreateOrderConnector::processEvent
     *
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT,
            sprintf('Method %s::processEvent is not supported!', ShoptetCreateOrderConnector::class)
        );

        $this->connector->processEvent($this->prepareProcessDto());
    }

}
