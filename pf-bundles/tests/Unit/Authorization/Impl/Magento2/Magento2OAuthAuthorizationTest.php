<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/17/17
 * Time: 1:33 PM
 */

namespace Tests\Unit\Authorization\Impl\Magento2;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Authorization\Base\AuthorizationInterface;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2Authorization;
use Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2OAuthAuthorization;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth1Provider;
use PHPUnit_Framework_MockObject_MockObject;
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
     */
    public function testGetAuthorizationType(): void
    {
        $this->assertEquals(AuthorizationInterface::OAUTH, $this->getMockedAuthorization()->getAuthorizationType());
    }

    /**
     * @covers Magento2OAuthAuthorization::buildDto()
     * @covers Magento2OAuthAuthorization::getHeaders()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetHeaders(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'api_key',
            'password_secret' => 'secret_key',
        ]);

        self::assertEquals([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer access_token',
        ], $authorization->getHeaders('GET', 'http://magento.com'));
    }

    /**
     * @covers Magento2Authorization::getReadMe()
     */
    public function testGetReadme(): void
    {
        $readme = $this->getMockedAuthorization()->getReadMe();

        $this->assertEquals(
            '[Name => Content]: [url => Connector URL] [username_key => Consumer Key] [password_secret => Consumer Secret]',
            $readme
        );
    }

    /**
     * @covers Magento2OAuthAuthorization::buildDto()
     * @covers Magento2OAuthAuthorization::getHeaders()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetHeadersMissingUrl(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => '',
            'username_key'    => 'api_key',
            'password_secret' => 'secret_key',
        ]);
        $authorization->getHeaders('GET', 'http://magento.com');
    }

    /**
     * @covers Magento2OAuthAuthorization::buildDto()
     * @covers Magento2OAuthAuthorization::getHeaders()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetHeadersMissingKey(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => '',
            'password_secret' => 'secret_key',
        ]);
        $authorization->getHeaders('GET', 'http://magento.com');
    }

    /**
     * @covers Magento2OAuthAuthorization::buildDto()
     * @covers Magento2OAuthAuthorization::getHeaders()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetHeadersMissingSecret(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'api_key',
            'password_secret' => '',
        ]);
        $authorization->getHeaders('GET', 'http://magento.com');
    }

    /**
     * @covers Magento2OAuthAuthorization::getUrl()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetUrl(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'api_key',
            'password_secret' => 'secret_key',
        ]);

        $this->assertEquals('url://magento2', $authorization->getUrl());
    }

    /**
     * @covers Magento2OAuthAuthorization::getUrl()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetSettingsMissingUrl(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => '',
            'username_key'    => 'api_key',
            'password_secret' => 'secret_key',
        ]);

        $authorization->getSettings();
    }

    /**
     * @covers Magento2OAuthAuthorization::getUrl()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetSettingsMissingKey(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => '',
            'password_secret' => 'secret_key',
        ]);

        $authorization->getSettings();
    }

    /**
     * @covers Magento2OAuthAuthorization::getUrl()
     * @covers Magento2OAuthAuthorization::getSettings()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testGetSettingsMissingSecret(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'api_key',
            'password_secret' => '',
        ]);

        $authorization->getSettings();
    }

    /**
     * @covers Magento2OAuthAuthorization::isAuthorized()
     */
    public function testIsAuthorized(): void
    {
        $this->assertTrue($this->getMockedAuthorization()->isAuthorized());
    }

    /**
     * @covers Magento2OAuthAuthorization::authorize()
     * @covers Magento2OAuthAuthorization::saveSettings()
     */
    public function testAuthorize(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'api_key',
            'password_secret' => 'secret_key',
        ]);

        $this->assertEmpty($authorization->authorize());
    }

    /**
     * @covers Magento2OAuthAuthorization::saveSettings()
     * @covers Magento2OAuthAuthorization::saveToken()
     */
    public function testSaveToken(): void
    {
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'api_key',
            'password_secret' => 'secret_key',
        ]);

        $this->assertEmpty($authorization->saveToken([]));
    }

    /**
     * @return Magento2OAuthAuthorization|PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockedAuthorization(): PHPUnit_Framework_MockObject_MockObject
    {
        $innerAuthorization = (new Authorization('magento2.oauth'))->setToken([
            'oauth_token'        => 'token',
            'oauth_token_secret' => 'secret',
        ]);

        $provider = $this->createMock(OAuth1Provider::class);
        $provider->method('getAuthorizeHeader')->willReturn('Bearer access_token');

        /** @var DocumentRepository|PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createPartialMock(DocumentRepository::class, ['findOneBy']);
        $repository->method('findOneBy')->willReturn($innerAuthorization);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $documentManager */
        $documentManager = $this->createPartialMock(DocumentManager::class, ['getRepository', 'flush']);
        $documentManager->method('getRepository')->willReturn($repository);
        $documentManager->method('flush')->willReturn(NULL);

        /** @var Magento2OAuthAuthorization|PHPUnit_Framework_MockObject_MockObject $authorization */
        $authorization = $this->getMockBuilder(Magento2OAuthAuthorization::class)
            ->setMethods(['getToken', 'loadAuthorization'])
            ->setConstructorArgs([
                $documentManager,
                $provider,
                'magento2.oauth',
                'Magento name',
                'Magento dsc',
            ])->getMock();

        $this->setProperty($authorization, 'authorization', $innerAuthorization);

        return $authorization;
    }

}