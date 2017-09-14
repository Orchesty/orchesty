<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/17/17
 * Time: 3:15 PM
 */

namespace Tests\Integration\Authorization\Impl\Magento2;

use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2Authorization;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class AuthorizationDBTest
 *
 * @package Tests\Integration\Authorization\Impl\Magento2
 */
class Magento2AuthorizationTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getHeaders()
     * @covers Magento2Authorization::isAuthorized()
     * @covers Magento2Authorization::getSettings()
     */
    public function testSaveLoad(): void
    {
        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'username',
            'password_secret' => 'password',
        ]);

        self::assertEquals([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer tokenizer',
        ], $authorization->getHeaders('GET', 'url'));
    }

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getHeaders()
     * @covers Magento2Authorization::isAuthorized()
     * @covers Magento2Authorization::getSettings()
     */
    public function testGetHeadersNoSettings(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $this->getMockedAuthorization()->getHeaders('GET', 'url');
    }

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getSettings()
     * @covers Magento2Authorization::saveSettings()
     */
    public function testSetSettingsMissingUrl(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => '',
            'username_key'    => 'username',
            'password_secret' => 'password',
        ]);
    }

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getSettings()
     * @covers Magento2Authorization::saveSettings()
     */
    public function testSetSettingsMissingUsername(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => '',
            'password_secret' => 'password',
        ]);
    }

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getSettings()
     * @covers Magento2Authorization::saveSettings()
     */
    public function testSetSettingsMissingPassword(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'url'             => 'url://magento2',
            'username_key'    => 'username',
            'password_secret' => '',
        ]);
    }

    /**
     * @return Magento2Authorization
     */
    private function getMockedAuthorization(): Magento2Authorization
    {
        $response = $this->createPartialMock(ResponseDto::class, ['getBody']);
        $response->method('getBody')->willReturn('{"token":"tokenizer"}');

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createPartialMock(CurlManagerInterface::class, ['send']);
        $curl->method('send')->willReturn($response);

        return new Magento2Authorization($this->dm, $curl, 'magento2.auth', 'Magento2 auth', 'Magento2 auth');
    }

}