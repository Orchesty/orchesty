<?php declare(strict_types=1);

namespace Tests\Unit\Authorization\Impl\Magento2;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Authorization\Base\AuthorizationInterface;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2OAuthAuthorization;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth1Provider;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class Magento2OAuthAuthorizationTest
 *
 * @package Tests\Unit\Authorization\Impl\Magento2
 */
final class Magento2OAuthAuthorizationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers Magento2OAuthAuthorization::getAuthorizationType()
     * @throws Exception
     */
    public function testGetAuthorizationType(): void
    {
        self::assertEquals(AuthorizationInterface::OAUTH, $this->getMockedAuthorization()->getAuthorizationType());
    }

    /**
     * @covers ::buildDto()
     * @covers ::getHeaders()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetHeaders(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'api_key',
            'field3' => 'secret_key',
        ]);

        self::assertEquals([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer access_token',
        ], $authorization->getHeaders('GET', 'http://magento.com'));
    }

    /**
     * @covers Magento2Authorization::getReadMe()
     * @throws Exception
     */
    public function testGetReadme(): void
    {
        $readme = $this->getMockedAuthorization()->getReadMe();

        self::assertEquals(
            'Field1 contains connector URL, field2 contains consumer key, field3 contains consumer secret.',
            $readme
        );
    }

    /**
     * @covers ::buildDto()
     * @covers ::getHeaders()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetHeadersMissingUrl(): void
    {
        self::expectException(AuthorizationException::class);
        self::expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => '',
            'field2' => 'api_key',
            'field3' => 'secret_key',
        ]);
        $authorization->getHeaders('GET', 'http://magento.com');
    }

    /**
     * @covers ::buildDto()
     * @covers ::getHeaders()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetHeadersMissingKey(): void
    {
        self::expectException(AuthorizationException::class);
        self::expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => '',
            'field3' => 'secret_key',
        ]);
        $authorization->getHeaders('GET', 'http://magento.com');
    }

    /**
     * @covers ::buildDto()
     * @covers ::getHeaders()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetHeadersMissingSecret(): void
    {
        self::expectException(AuthorizationException::class);
        self::expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'api_key',
            'field3' => '',
        ]);
        $authorization->getHeaders('GET', 'http://magento.com');
    }

    /**
     * @covers ::getUrl()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetUrl(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'api_key',
            'field3' => 'secret_key',
        ]);

        self::assertEquals('url://magento2', $authorization->getUrl());
    }

    /**
     * @covers ::getUrl()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetSettingsMissingUrl(): void
    {
        self::expectException(AuthorizationException::class);
        self::expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => '',
            'field2' => 'api_key',
            'field3' => 'secret_key',
        ]);

        $authorization->getSettings();
    }

    /**
     * @covers ::getUrl()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetSettingsMissingKey(): void
    {
        self::expectException(AuthorizationException::class);
        self::expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => '',
            'field3' => 'secret_key',
        ]);

        $authorization->getSettings();
    }

    /**
     * @covers ::getUrl()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetSettingsMissingSecret(): void
    {
        self::expectException(AuthorizationException::class);
        self::expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'api_key',
            'field3' => '',
        ]);

        $authorization->getSettings();
    }

    /**
     * @covers ::isAuthorized()
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        self::assertTrue($this->getMockedAuthorization()->isAuthorized());
    }

    /**
     * @covers ::authorize()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'api_key',
            'field3' => 'secret_key',
        ]);

        self::assertEmpty($authorization->authorize());
    }

    /**
     * @covers ::saveSettings()
     * @covers ::saveToken()
     * @throws Exception
     */
    public function testSaveToken(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'api_key',
            'field3' => 'secret_key',
        ]);

        self::assertEmpty($authorization->saveToken([]));
    }

    /**
     * @return Magento2OAuthAuthorization|MockObject
     * @throws Exception
     */
    private function getMockedAuthorization(): MockObject
    {
        $innerAuthorization = (new Authorization('magento2.oauth'))->setToken([
            'oauth_token'        => 'token',
            'oauth_token_secret' => 'secret',
        ]);

        $provider = self::createMock(OAuth1Provider::class);
        $provider->method('getAuthorizeHeader')->willReturn('Bearer access_token');

        /** @var DocumentRepository|MockObject $repository */
        $repository = self::createPartialMock(DocumentRepository::class, ['findOneBy']);
        $repository->method('findOneBy')->willReturn($innerAuthorization);

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = self::createPartialMock(DocumentManager::class, ['getRepository', 'flush']);
        $documentManager->method('getRepository')->willReturn($repository);
        $documentManager->method('flush')->willReturn(NULL);

        /** @var Magento2OAuthAuthorization|MockObject $authorization */
        $authorization = self::getMockBuilder(Magento2OAuthAuthorization::class)
            ->setMethods(['getToken', 'loadAuthorization'])
            ->setConstructorArgs([
                $documentManager,
                $provider,
                'magento2.oauth',
                'Magento name',
                'Magento dsc',
                '127.0.0.4',
            ])->getMock();

        $this->setProperty($authorization, 'authorization', $innerAuthorization);

        return $authorization;
    }

}
