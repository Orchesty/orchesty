<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
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

    private const ID          = 'id';
    private const TYPE        = 'type';
    private const EXTERNAL_ID = 'external-id';
    private const USER        = 'user';
    private const SENDER      = 'sender';

    private const HEADERS = [
        'user'            => self::USER,
        self::TYPE        => 'cancelled',
        'application'     => ShoptetApplication::SHOPTET_KEY,
        'internal-id'     => '1',
        self::EXTERNAL_ID => '1',
    ];

    private const SETTINGS = [
        ApplicationInterface::AUTHORIZATION_FORM => [
            'cancelled'                  => -1,
            ShoptetApplication::ESHOP_ID => 125,
        ],
        'clientSettings'                         => [
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
    private ShoptetUpdateOrderConnector $connector;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('shoptet-update-order', $this->connector->getName());
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
        $this->pfd($applicationInstall);

        $dto = $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                self::HEADERS + [
                    self::ID => $applicationInstall->getId(),
                ],
            ),
        );
        $this->dm->clear();

        self::assertEquals('', Json::decode($dto->getData())['errors']);
        self::assertArrayHasKey('user', $dto->getHeaders());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     * @throws Exception
     */
    public function testProcessActionMissingHeader(): void
    {

        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS,
        );
        $this->pfd($applicationInstall);

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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::getApplicationInstall
     *
     * @throws Exception
     */
    public function testProcessActionMissingApplicationInstall(): void
    {

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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetUpdateOrderConnector::processAction
     *
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
        $this->pfd($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::USER        => self::USER,
                    self::EXTERNAL_ID => '1',
                    self::ID          => $applicationInstall->getId(),
                ],
            ),
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
        $this->pfd($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::EXTERNAL_ID => '1',
                    self::TYPE        => 'Type',
                    self::USER        => self::USER,
                    self::ID          => $applicationInstall->getId(),
                ],
            ),
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
        $this->pfd($applicationInstall);

        $this->connector->processAction(
            $this->prepareProcessDto(
                [],
                [
                    self::USER        => self::USER,
                    self::EXTERNAL_ID => '1',
                    self::ID          => $applicationInstall->getId(),
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

        $this->connector = self::getContainer()->get('hbpf.connector.shoptet-update-order');
    }

}
