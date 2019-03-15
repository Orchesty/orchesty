<?php declare(strict_types=1);

namespace Tests\Integration\Authorization\Impl\Magento2;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2Authorization;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class Magento2AuthorizationTest
 *
 * @package Tests\Integration\Authorization\Impl\Magento2
 */
final class Magento2AuthorizationTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getHeaders()
     * @covers Magento2Authorization::isAuthorized()
     * @covers Magento2Authorization::getSettings()
     * @throws Exception
     */
    public function testSaveLoad(): void
    {
        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'username',
            'field3' => 'password',
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
     * @throws Exception
     */
    public function testGetHeadersNoSettings(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        $this->getMockedAuthorization()->getHeaders('GET', 'url');
    }

    /**
     * @covers Magento2Authorization::getReadMe()
     * @throws Exception
     */
    public function testGetReadme(): void
    {
        $readme = $this->getMockedAuthorization()->getReadMe();

        $this->assertEquals(
            'Field1 contains connector URL, field2 contains username, field3 contains password.',
            $readme
        );
    }

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getSettings()
     * @covers Magento2Authorization::saveSettings()
     * @throws Exception
     */
    public function testSetSettingsMissingUrl(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => '',
            'field2' => 'username',
            'field3' => 'password',
        ]);
    }

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getSettings()
     * @covers Magento2Authorization::saveSettings()
     * @throws Exception
     */
    public function testSetSettingsMissingUsername(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => '',
            'field3' => 'password',
        ]);
    }

    /**
     * @covers Magento2Authorization::authorize()
     * @covers Magento2Authorization::getSettings()
     * @covers Magento2Authorization::saveSettings()
     * @throws Exception
     */
    public function testSetSettingsMissingPassword(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND);

        /** @var Magento2Authorization $authorization */
        $authorization = $this->getMockedAuthorization();
        $authorization->saveSettings([
            'field1' => 'url://magento2',
            'field2' => 'username',
            'field3' => '',
        ]);
    }

    /**
     * @return Magento2Authorization
     * @throws Exception
     */
    private function getMockedAuthorization(): Magento2Authorization
    {
        $response = $this->createPartialMock(ResponseDto::class, ['getBody']);
        $response->method('getBody')->willReturn('{"token":"tokenizer"}');

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createPartialMock(CurlManagerInterface::class, ['send']);
        $curl->method('send')->willReturn($response);

        return new Magento2Authorization($this->dm, $curl, 'magento2_auth', 'Magento2Old auth', 'Magento2Old auth');
    }

}
