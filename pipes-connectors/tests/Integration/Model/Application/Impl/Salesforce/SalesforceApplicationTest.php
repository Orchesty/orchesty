<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Salesforce;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class SalesforceApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Salesforce
 */
#[CoversClass(SalesforceApplication::class)]
final class SalesforceApplicationTest extends KernelTestCaseAbstract
{

    private const CLIENT_ID = '123****';

    /**
     * @var SalesforceApplication
     */
    private SalesforceApplication $application;

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
            'salesforce',
            $this->application->getName(),
        );
    }

    /**
     * @return void
     */
    public function testGetName(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Salesforce',
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
            'Salesforce is one of the largest CRM platform.',
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
                    [
                        OAuth2ApplicationAbstract::CLIENT_ID,
                        OAuth2ApplicationAbstract::CLIENT_SECRET,
                        'instance_name',
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
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName());

        $dto = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://yourInstance.salesforce.com/services/data/v20.0/sobjects/Account/',
            'body',
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
            'https://login.salesforce.com/services/oauth2/authorize',
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
            'https://login.salesforce.com/services/oauth2/token',
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
        self::assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect('https://login.salesforce.com/services/oauth2/authorize', self::CLIENT_ID);
        $this->application = self::getContainer()->get('hbpf.application.salesforce');
    }

}
