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
 * @coversDefaultClass Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2Authorization
 * @package Tests\Unit\Authorization\Impl\Magento2Old
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
     */
    public function testGetReadme(): void
    {
        $readme = $this->getMockedAuthorization()->getReadMe();

        $this->assertEquals(
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
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

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
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

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
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

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

        $this->assertEquals('url://magento2', $authorization->getUrl());
    }

    /**
     * @covers ::getUrl()
     * @covers ::getSettings()
     * @covers ::saveSettings()
     * @throws Exception
     */
    public function testGetSettingsMissingUrl(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

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
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

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
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

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
     */
    public function testIsAuthorized(): void
    {
        $this->assertTrue($this->getMockedAuthorization()->isAuthorized());
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

        $this->assertEmpty($authorization->authorize());
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

        $this->assertEmpty($authorization->saveToken([]));
    }

    /**
     * @return Magento2OAuthAuthorization|MockObject
     */
    private function getMockedAuthorization(): MockObject
    {
        $innerAuthorization = (new Authorization('magento2.oauth'))->setToken([
            'oauth_token'        => 'token',
            'oauth_token_secret' => 'secret',
        ]);

        $provider = $this->createMock(OAuth1Provider::class);
        $provider->method('getAuthorizeHeader')->willReturn('Bearer access_token');

        /** @var DocumentRepository|MockObject $repository */
        $repository = $this->createPartialMock(DocumentRepository::class, ['findOneBy']);
        $repository->method('findOneBy')->willReturn($innerAuthorization);

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = $this->createPartialMock(DocumentManager::class, ['getRepository', 'flush']);
        $documentManager->method('getRepository')->willReturn($repository);
        $documentManager->method('flush')->willReturn(NULL);

        /** @var Magento2OAuthAuthorization|MockObject $authorization */
        $authorization = $this->getMockBuilder(Magento2OAuthAuthorization::class)
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