<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Zoho;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ZohoApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Zoho
 */
class ZohoApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '123';

    /**
     * @var ZohoApplication
     */
    private $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(
            ApplicationTypeEnum::CRON,
            $this->application->getApplicationType()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getKey
     */
    public function testGetKey(): void
    {
        $this->setApplication();
        self::assertEquals(
            'zoho',
            $this->application->getKey()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getName
     */
    public function testGetName(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zoho',
            $this->application->getName()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getDescription
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zoho is a provider of a Customer Relationship Management (CRM) solution',
            $this->application->getDescription()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $this->setApplication();
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains(
                $field->getKey(),
                [
                    OAuth2ApplicationAbstract::CLIENT_ID,
                    OAuth2ApplicationAbstract::CLIENT_SECRET,
                ]
            );
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getRequestDto
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getKey());
        $this->pf($applicationInstall);

        $dto = $this->application->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://www.zohoapis.com/crm/v2/settings/modules',
            'data'
        );

        self::assertEquals(
            $dto->getHeaders(),
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer token123',
            ]
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getAuthUrl
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://accounts.zoho.eu/oauth/v2/auth',
            $this->application->getAuthUrl()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getTokenUrl
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://accounts.zoho.eu/oauth/v2/token',
            $this->application->getTokenUrl()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::authorize
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getScopes
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getKey(),
            'user',
            'token123',
            self::CLIENT_ID
        );
        $this->pf($applicationInstall);
        $this->assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
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
            'ZohoCRM.modules.ALL ZohoCRM.settings.ALL'
        );

        $this->application = self::$container->get('hbpf.application.zoho');
    }

}
