<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetEshopInfo;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class ShoptetGetEshopInfoTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shoptet\Connector
 */
#[CoversClass(ShoptetGetEshopInfo::class)]
final class ShoptetGetEshopInfoTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    private const array HEADERS = [
        'application' => ShoptetApplication::SHOPTET_KEY,
        'user'        => 'user',
    ];

    /**
     * @var ShoptetGetEshopInfo
     */
    private ShoptetGetEshopInfo $connector;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @return void
     */
    public function testGetName(): void
    {
        self::assertSame('shoptet-get-eshop-info', $this->connector->getName());
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws CustomNodeException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $jsonContent = File::getContent(__DIR__ . '/data/ShoptetGetEshopInfo.json');
        $this->mockSender($jsonContent);

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->insertApplicationInstall()->toArray()])),
            ),
        );

        $dto  = (new ProcessDto())->setHeaders(self::HEADERS);
        $data = $this->connector->processAction($dto);

        self::assertEquals(self::HEADERS, $data->getHeaders());
    }

    /**
     * @throws Exception
     */
    public function testProcessActionArray(): void
    {
        $jsonContent = File::getContent(__DIR__ . '/data/ShoptetGetEshopInfo.json');
        $this->mockSender($jsonContent);
        $applicationInstall = $this->insertApplicationInstall();
        $data               = $this->connector->processActionArray($applicationInstall, new ProcessDto());

        self::assertNotEmpty($data);
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testProcessActionErr(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shoptet"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->insertApplicationInstall()->toArray()])),
            ),
        );

        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-get-eshop-info': %s: Something gone wrong!", CurlException::class),
        );

        $this->setProperty(
            $this->connector,
            'sender',
            $this->prepareSender($this->prepareSenderErrorResponse()),
        );

        $this->connector->processAction($this->prepareProcessDto('{"data":"data"}', self::HEADERS));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->connector = self::getContainer()->get('hbpf.connector.shoptet-get-eshop-info');
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function insertApplicationInstall(): ApplicationInstall
    {
        return DataProvider::createApplicationInstall(
            ShoptetApplication::SHOPTET_KEY,
            'user',
            [
                'clientSettings' => [
                    'token' => [
                        'access_token' => 'Access Token',
                        'expires_in'   => DateTimeUtils::getUtcDateTime('1 day')->getTimestamp(),
                    ],
                ],
            ],
            [
                'getApiKey' => ['receivingStatus' => 'unlock'],
            ],
        );
    }

    /**
     * @param string $jsonContent
     *
     * @throws Exception
     */
    private function mockSender(string $jsonContent): void
    {
        $this->setProperty(
            $this->connector,
            'sender',
            $this->prepareSender(static fn() => new ResponseDto(200, 'Created', $jsonContent, self::HEADERS)),
        );
    }

}
