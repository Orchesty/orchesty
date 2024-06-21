<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Quickbooks;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class QuickbooksApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Quickbooks
 */
#[CoversClass(QuickbooksApplication::class)]
final class QuickbooksApplicationTest extends KernelTestCaseAbstract
{

    private const CLIENT_ID     = 'ABnInj8B7FNcPOCg5AMBjMLM2XFSU4Al127Yb4qe9AuVO*****';
    private const CLIENT_SECRET = 'HgEucBQMQxiQZMHppzuQzSOabBqPKmXDTH0*****';
    private const SHOP_ID       = '13456789';

    /**
     * @var QuickbooksApplication
     */
    private QuickbooksApplication $application;

    /**
     * @return void
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON->value, $this->application->getApplicationType());
    }

    /**
     * @return void
     */
    public function testGetKey(): void
    {
        self::assertEquals('quickbooks', $this->application->getName());
    }

    /**
     * @return void
     */
    public function testPublicName(): void
    {
        self::assertEquals('Quickbooks', $this->application->getPublicName());
    }

    /**
     * @return void
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Quickbooks v1', $this->application->getDescription());
    }

    /**
     * @return void
     */
    public function testAuthUrl(): void
    {
        self::assertEquals('https://appcenter.intuit.com/connect/oauth2', $this->application->getAuthUrl());
    }

    /**
     * @return void
     */
    public function testTokenUrl(): void
    {
        self::assertEquals(
            'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
            $this->application->getTokenUrl(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains(
                    $field->getKey(),
                    [
                        'app_id',
                        OAuth2ApplicationInterface::CLIENT_ID,
                        OAuth2ApplicationInterface::CLIENT_SECRET,
                    ],
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET,
        );
        $applicationInstall->addSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => array_merge(
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM],
                    [QuickbooksApplication::APP_ID => self::SHOP_ID],
                ),
            ],
        );

        $dto = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            '/account',
            '{"data":"oooo"}',
        );

        self::assertEquals(
            [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer token',
                'Content-Type'  => 'application/json',
            ],
            $dto->getHeaders(),
        );
        self::assertEquals('https://quickbooks.api.intuit.com/v3/company/13456789/account', $dto->getUri());
        self::assertEquals('{"data":"oooo"}', $dto->getBody());
        self::assertEquals('POST', $dto->getMethod());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.quickbooks');
    }

}
