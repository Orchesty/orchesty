<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class IDokladApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad
 */
final class IDokladApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var IDokladApplication
     */
    private IDokladApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication::getKey
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals('i-doklad', $this->app->getKey());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication::getName
     *
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals('iDoklad Application', $this->app->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication::getDescription
     *
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertEquals('iDoklad Application', $this->app->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication::getAuthUrl
     *
     * @throws Exception
     */
    public function testGetAuthUrl(): void
    {
        self::assertEquals(IDokladApplication::AUTH_URL, $this->app->getAuthUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication::getTokenUrl
     *
     * @throws Exception
     */
    public function testGetTokenUrl(): void
    {
        self::assertEquals(IDokladApplication::TOKEN_URL, $this->app->getTokenUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $form = $this->app->getSettingsForm();
        self::assertCount(2, $form->getFields());
    }

    /**
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $dto = $this->app->getRequestDto(
            DataProvider::getOauth2AppInstall($this->app->getKey()),
            CurlManager::METHOD_POST,
            NULL,
            Json::encode(['foo' => 'bar']),
        );
        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(IDokladApplication::BASE_URL, $dto->getUri(TRUE));
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
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
        $this->app = new IDokladApplication($provider);
    }

}
