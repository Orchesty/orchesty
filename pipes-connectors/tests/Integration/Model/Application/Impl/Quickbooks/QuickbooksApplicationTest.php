<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Quickbooks;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class QuickbooksApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Quickbooks
 */
final class QuickbooksApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID     = 'ABnInj8B7FNcPOCg5AMBjMLM2XFSU4Al127Yb4qe9AuVO*****';
    private const CLIENT_SECRET = 'HgEucBQMQxiQZMHppzuQzSOabBqPKmXDTH0*****';
    private const SHOP_ID       = '13456789';

    /**
     * @var QuickbooksApplication
     */
    private $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getKey
     */
    public function testGetKey(): void
    {
        self::assertEquals('quickbooks', $this->application->getKey());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getName
     */
    public function testName(): void
    {
        self::assertEquals('Quickbooks', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getDescription
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Quickbooks v1', $this->application->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getAuthUrl
     */
    public function testAuthUrl(): void
    {
        self::assertEquals('https://appcenter.intuit.com/connect/oauth2', $this->application->getAuthUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getTokenUrl
     */
    public function testTokenUrl(): void
    {
        self::assertEquals(
            'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
            $this->application->getTokenUrl()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertContains(
                $field->getKey(),
                [
                    'app_id',
                    OAuth2ApplicationInterface::CLIENT_ID,
                    OAuth2ApplicationInterface::CLIENT_SECRET,
                ]
            );
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getBaseUrl
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getKey(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
        $applicationInstall->setSettings(
            [BasicApplicationAbstract::FORM => [QuickbooksApplication::APP_ID => self::SHOP_ID]]
        );
        $this->pfd($applicationInstall);

        $dto = $this->application->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            '/account',
            '{"data":"oooo"}'
        );

        self::assertEquals(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer token',
            ],
            $dto->getHeaders()
        );
        self::assertEquals('https://quickbooks.api.intuit.com/v3/company/13456789/account', $dto->getUri());
        self::assertEquals('{"data":"oooo"}', $dto->getBody());
        self::assertEquals('POST', $dto->getMethod());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication::getScopes
     *
     * @throws Exception
     */
    public function testGetScopes(): void
    {
        $scopes = $this->invokeMethod($this->application, 'getScopes', [new ApplicationInstall()]);

        self::assertEquals(['com.intuit.quickbooks.accounting'], $scopes);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.quickbooks');
    }

}
