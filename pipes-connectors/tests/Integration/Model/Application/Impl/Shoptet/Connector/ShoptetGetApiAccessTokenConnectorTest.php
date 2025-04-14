<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetApiAccessTokenConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class ShoptetGetApiAccessTokenConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector
 */
#[CoversClass(ShoptetGetApiAccessTokenConnector::class)]
final class ShoptetGetApiAccessTokenConnectorTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    private const array HEADERS = [
        'application' => ShoptetApplication::SHOPTET_KEY,
        'user'        => 'user',
    ];

    private const string API_TOKEN_URL = 'https://12345.myshoptet.com/action/ApiOAuthServer/getAccessToken';

    /**
     * @var ShoptetGetApiAccessTokenConnector
     */
    private ShoptetGetApiAccessTokenConnector $connector;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertSame('shoptet-get-access-token', $this->connector->getName());
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockSender();

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->insertApplicationInstall()->toArray()])),
            ),
        );

        $data = $this->connector->processAction((new ProcessDto())->setHeaders(self::HEADERS));

        self::assertSame('{"data":"data"}', $data->getData());
    }

    /**
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
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->connector = self::getContainer()->get('hbpf.connector.shoptet-get-api-access-token');
    }

    /**
     * @return ApplicationInstall
     *
     * @throws Exception
     */
    private function insertApplicationInstall(): ApplicationInstall
    {
        return DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            'user',
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '___access_token__'],
                    ShoptetApplication::API_TOKEN_URL => self::API_TOKEN_URL,
                ],
            ],
        );
    }

    /**
     * @throws Exception
     */
    private function mockSender(): void
    {
        $callback = static fn() => new ResponseDto(
            200,
            '',
            '{"data":"data"}',
            [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', '___access_token__'),
                'Content-Type'  => 'application/json',
            ],
        );

        $this->setProperty(
            $this->connector,
            'sender',
            $this->prepareSender($callback),
        );
    }

}
