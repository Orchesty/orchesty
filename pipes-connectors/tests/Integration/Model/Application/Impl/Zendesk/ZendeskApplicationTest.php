<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Zendesk;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

/**
 * Class ZendeskApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Zendesk
 */
#[CoversClass(ZendeskApplication::class)]
final class ZendeskApplicationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var ZendeskApplication
     */
    private ZendeskApplication $application;

    /**
     * @return void
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertSame(
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
        self::assertSame(
            'zendesk',
            $this->application->getName(),
        );
    }

    /**
     * @return void
     */
    public function testGetPublicName(): void
    {
        $this->setApplication();
        self::assertSame(
            'Zendesk',
            $this->application->getPublicName(),
        );
    }

    /**
     * @return void
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertSame(
            'Zendesk is a customer support software. It helps companies and organisations manage customer queries and problems through a ticketing system.',
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
                        'subdomain',
                    ],
                );
            }
        }
    }

    /**
     * @throws CurlException
     * @throws ApplicationInstallException
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
            'https://hanaboso.zendesk.com/api/v2/users',
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
     * @throws Exception
     */
    public function testGetAuthUrlWithSubdomain(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->setSettings([ApplicationInterface::AUTHORIZATION_FORM => ['subdomain' => 'domain123']]);

        $authUrl = $this->application->getAuthUrlWithSubdomain($applicationInstall);

        self::assertSame('https://domain123.zendesk.com/oauth/authorizations/new', $authUrl);
    }

    /**
     * @throws Exception
     */
    public function testGetTokenUrlWithDomain(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings([ApplicationInterface::AUTHORIZATION_FORM => ['subdomain' => 'domain123']]);

        $authUrl = $this->application->getTokenUrlWithSubdomain($applicationInstall);

        self::assertSame('https://domain123.zendesk.com/oauth/tokens', $authUrl);
    }

    /**
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName());
        $applicationInstall->addSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => array_merge(
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM],
                    ['subdomain' => 'domain123'],
                ),
            ],
        );

        $this->application->authorize($applicationInstall);
        self::assertTrue($this->application->isAuthorized($applicationInstall));
    }

    /**
     * @return void
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertSame('', $this->application->getAuthUrl());
    }

    /**
     * @return void
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertSame('', $this->application->getTokenUrl());
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function testCreateDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings([ApplicationInterface::AUTHORIZATION_FORM => ['subdomain' => 'domain123']]);

        $crateDto = $this->invokeMethod(
            $this->application,
            'createDto',
            [$applicationInstall, 'https://127.0.0.66/api/applications/authorize/token'],
        );

        self::assertEquals('https://127.0.0.66/api/applications/authorize/token', $crateDto->getRedirectUrl());
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect('https://domain123.zendesk.com/oauth/authorizations/new', 'clientId', 'read write');
        $this->application = self::getContainer()->get('hbpf.application.zendesk');
    }

}
