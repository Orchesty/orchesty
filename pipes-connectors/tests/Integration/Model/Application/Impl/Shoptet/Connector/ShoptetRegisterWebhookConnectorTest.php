<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetRegisterWebhookConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShoptetRegisterWebhookConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shoptet\Connector
 */
class ShoptetRegisterWebhookConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const USER   = 'user';
    private const SENDER = 'sender';

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
     * @var ShoptetRegisterWebhookConnector
     */
    private $connector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.shoptet-register-webhook');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetRegisterWebhookConnector::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('shoptet-register-webhook-connector', $this->connector->getId());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetRegisterWebhookConnector::processAction
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
                $this->prepareSenderResponse('{"data":null}')
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
                self::HEADERS
            )
        );

        self::assertEquals([], Json::decode($dto->getData()));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetRegisterWebhookConnector::processAction
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetConnectorAbstract::processResponse
     *
     * @throws Exception
     */
    public function testProcessActionException(): void
    {
        $applicationInstall = DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            self::USER,
            self::SETTINGS,
            self::NON_ENCRYPTED_SETTINGS
        );
        $this->pf($applicationInstall);

        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-register-webhook-connector': %s: Something gone wrong!", CurlException::class)
        );

        $this->setProperty(
            $this->connector,
            self::SENDER,
            $this->prepareSender(
                $this->prepareSenderErrorResponse()
            )
        );
        $this->connector->processAction($this->prepareProcessDto([])->setHeaders(self::HEADERS));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetRegisterWebhookConnector::processEvent
     *
     * @throws Exception
     */
    public function testProcessEventMissingEventInstance(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT,
            sprintf('Method %s::processEvent is not supported!', ShoptetRegisterWebhookConnector::class)
        );

        $this->connector->processEvent($this->prepareProcessDto());
    }

}
