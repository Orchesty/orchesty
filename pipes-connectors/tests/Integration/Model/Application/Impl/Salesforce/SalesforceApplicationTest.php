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

/**
 * Class SalesforceApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Salesforce
 */
final class SalesforceApplicationTest extends KernelTestCaseAbstract
{

    private const CLIENT_ID = '123****';

    /**
     * @var SalesforceApplication
     */
    private SalesforceApplication $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getApplicationType
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getName
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getPublicName
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getDescription
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getFormStack
     *
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getRequestDto
     *
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getAuthUrl
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::getTokenUrl
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Salesforce\SalesforceApplication::authorize
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
