<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class GoogleDriveApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\GoogleDrive
 */
final class GoogleDriveApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var GoogleDriveApplication
     */
    private GoogleDriveApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getKey
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals('google-drive', $this->app->getKey());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getName
     *
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals('GoogleDrive Application', $this->app->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getDescription
     *
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertEquals('GoogleDrive Application', $this->app->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getAuthUrl
     *
     * @throws Exception
     */
    public function testGetAuthUrl(): void
    {
        self::assertEquals(GoogleDriveApplication::AUTH_URL, $this->app->getAuthUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getTokenUrl
     *
     * @throws Exception
     */
    public function testGetTokenUrl(): void
    {
        self::assertEquals(GoogleDriveApplication::TOKEN_URL, $this->app->getTokenUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getRequestDto
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $dto = $this->app->getRequestDto(
            DataProvider::getOauth2AppInstall($this->app->getKey()),
            CurlManager::METHOD_POST,
            NULL,
            Json::encode(['foo' => 'bar'])
        );
        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(GoogleDriveApplication::BASE_URL, $dto->getUri(TRUE));
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $form = $this->app->getSettingsForm();
        self::assertCount(2, $form->getFields());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::authorize
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication::getScopes
     *
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->app->authorize(DataProvider::getOauth2AppInstall($this->app->getKey()));

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
