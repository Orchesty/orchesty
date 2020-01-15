<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shoptet\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetEshopInfo;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use ReflectionException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShoptetGetEshopInfoTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetGetEshopInfoTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const HEADERS = [
        'pf-user'        => 'user',
        'pf-application' => ShoptetApplication::SHOPTET_KEY,
    ];

    /**
     * @var ShoptetGetEshopInfo
     */
    private $connector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = self::$container->get('hbpf.connector.shoptet-get-eshop-info');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetEshopInfo::getId()
     */
    public function testGetId(): void
    {
        self::assertEquals('shoptet-get-eshop-info', $this->connector->getId());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetEshopInfo::processEvent()
     *
     * @throws ConnectorException
     */
    public function testProcessEvent(): void
    {
        self::expectException(ConnectorException::class);
        $this->connector->processEvent(new ProcessDto());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetEshopInfo::processAction()
     *
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws OnRepeatException
     * @throws ReflectionException
     */
    public function testProcessAction(): void
    {
        $jsonContent = (string) file_get_contents(__DIR__ . '/data/ShoptetGetEshopInfo.json');
        $this->mockSender($jsonContent);
        $this->insertApplicationInstall();

        $dto  = (new ProcessDto())->setHeaders(self::HEADERS);
        $data = $this->connector->processAction($dto);

        self::assertEquals(self::HEADERS, $data->getHeaders());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetEshopInfo::processActionArray()
     *
     * @throws CurlException
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testProcessActionArray(): void
    {
        $jsonContent = (string) file_get_contents(__DIR__ . '/data/ShoptetGetEshopInfo.json');
        $this->mockSender($jsonContent);
        $applicationInstall = $this->insertApplicationInstall();
        $data               = $this->connector->processActionArray($applicationInstall, new ProcessDto());

        self::assertIsArray($data);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector\ShoptetGetEshopInfo::processAction()
     *
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws OnRepeatException
     * @throws ReflectionException
     */
    public function testProcessActionErr(): void
    {
        $this->insertApplicationInstall();
        self::assertException(
            OnRepeatException::class,
            CurlException::REQUEST_FAILED,
            sprintf("Connector 'shoptet-get-eshop-info': %s: Something gone wrong!", CurlException::class)
        );

        $this->setProperty(
            $this->connector,
            'sender',
            $this->prepareSender($this->prepareSenderErrorResponse())
        );

        $this->connector->processAction($this->prepareProcessDto('{"data":"data"}', self::HEADERS));
    }

    /**
     * @return ApplicationInstall
     * @throws DateTimeException
     * @throws Exception
     */
    private function insertApplicationInstall(): ApplicationInstall
    {
        $applicationInstall = DataProvider::createApplicationInstall(
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
            ]
        );
        $this->pf($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param string $jsonContent
     *
     * @throws ReflectionException
     * @throws Exception
     */
    private function mockSender(string $jsonContent): void
    {
        $this->setProperty(
            $this->connector,
            'sender',
            $this->prepareSender(
                static fn() => new ResponseDto(200, 'Created', $jsonContent, self::HEADERS)
            )
        );
    }

}
