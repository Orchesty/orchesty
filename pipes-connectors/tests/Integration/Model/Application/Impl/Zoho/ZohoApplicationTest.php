<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Zoho;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class ZohoApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Zoho
 */
#[CoversClass(ZohoApplication::class)]
final class ZohoApplicationTest extends KernelTestCaseAbstract
{

    private const CLIENT_ID = '123';

    /**
     * @var ZohoApplication
     */
    private ZohoApplication $application;

    /**
     * @return void
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(
            ApplicationTypeEnum::CRON->value,
            $this->application->getApplicationType(),
        );
    }

    /**
     * @return void
     */
    public function testGetKey(): void
    {
        $this->setApplication();
        self::assertEquals(
            'zoho',
            $this->application->getName(),
        );
    }

    /**
     * @return void
     */
    public function testGetPublicName(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zoho',
            $this->application->getPublicName(),
        );
    }

    /**
     * @return void
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zoho is a provider of a Customer Relationship Management (CRM) solution',
            $this->application->getDescription(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $this->setApplication();
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContainsEquals(
                    $field->getKey(),
                    [OAuth2ApplicationAbstract::CLIENT_ID, OAuth2ApplicationAbstract::CLIENT_SECRET],
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName());

        $dto = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://www.zohoapis.com/crm/v2/settings/modules',
            'data',
        );

        self::assertEquals(
            [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer token123',
                'Content-Type'  => 'application/json',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @return void
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://accounts.zoho.eu/oauth/v2/auth',
            $this->application->getAuthUrl(),
        );
    }

    /**
     * @return void
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://accounts.zoho.eu/oauth/v2/token',
            $this->application->getTokenUrl(),
        );
    }

    /**
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'token123',
            self::CLIENT_ID,
        );
        self::assertTrue($this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect(
            'https://accounts.zoho.eu/oauth/v2/auth',
            self::CLIENT_ID,
            'ZohoCRM.modules.ALL ZohoCRM.settings.ALL',
        );
        $this->application = self::getContainer()->get('hbpf.application.zoho');
    }

}
