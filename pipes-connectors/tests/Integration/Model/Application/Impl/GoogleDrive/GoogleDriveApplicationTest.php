<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class GoogleDriveApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive
 */
#[CoversClass(GoogleDriveApplication::class)]
final class GoogleDriveApplicationTest extends KernelTestCaseAbstract
{

    /**
     * @var GoogleDriveApplication
     */
    private GoogleDriveApplication $app;

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertSame('google-drive', $this->app->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetPublicName(): void
    {
        self::assertSame('GoogleDrive Application', $this->app->getPublicName());
    }

    /**
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertSame('GoogleDrive Application', $this->app->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testGetAuthUrl(): void
    {
        self::assertSame(GoogleDriveApplication::AUTH_URL, $this->app->getAuthUrl());
    }

    /**
     * @throws Exception
     */
    public function testGetTokenUrl(): void
    {
        self::assertSame(GoogleDriveApplication::TOKEN_URL, $this->app->getTokenUrl());
    }

    /**
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $dto = $this->app->getRequestDto(
            new ProcessDto(),
            DataProvider::getOauth2AppInstall($this->app->getName()),
            CurlManager::METHOD_POST,
            NULL,
            Json::encode(['foo' => 'bar']),
        );
        self::assertSame(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(GoogleDriveApplication::BASE_URL, $dto->getUri(TRUE));
        self::assertSame(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->app->getFormStack()->getForms();
        foreach ($forms as $form) {
            self::assertCount(2, $form->getFields());
        }
    }

    /**
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->app->authorize(DataProvider::getOauth2AppInstall($this->app->getName()));
        self::assertFake();
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $provider = self::createMock(OAuth2Provider::class);
        $provider->method('authorize')->willReturnCallback(static fn(): string => 'redirect/url');
        $this->app = new GoogleDriveApplication($provider);
    }

}
