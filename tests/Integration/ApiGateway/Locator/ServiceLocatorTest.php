<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\ApiGateway\Locator;

use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\Utils\String\Json;
use LogicException;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Psr\Log\NullLogger;

/**
 * Class ServiceLocatorTest
 *
 * @package PipesFrameworkTests\Integration\ApiGateway\Locator
 */
final class ServiceLocatorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getApps
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::setLogger
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::__construct
     *
     * @throws Exception
     */
    public function testGetApps(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'null']]]), []);

        $res = $this->createLocator($dto)->getApps();
        self::assertEquals(['items' => [['key' => 'null']]], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getApp
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testGetApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->getApp('null');
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getUserApps
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testGetUserApps(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'null']]]), []);

        $res = $this->createLocator($dto)->getUserApps('user');
        self::assertEquals(['items' => [['key' => 'null']]], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getAppDetail
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testGetAppDetail(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->getAppDetail('null', 'user');
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::installApp
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testInstallApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->installApp('null', 'user');
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::uninstallApp
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testUninstallApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->uninstallApp('null', 'user');
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::updateApp
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testUpdateApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->updateApp('null', 'user', ['form']);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::updateAppPassword
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testUpdateAppPassword(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->updateAppPassword('null', 'user', ['pass']);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::authorizationToken
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::queryToString
     *
     * @throws Exception
     */
    public function testAuthorizationToken(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->authorizationToken('null', 'user', ['param1' => 'aaa', 'state' => ['abc']]);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::authorizationQueryToken
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::queryToString
     *
     * @throws Exception
     */
    public function testAuthorizationQueryToken(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->authorizationQueryToken(['param1' => 'aaa', 'state' => ['abc']]);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::authorize
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $this->createLocator($dto)->authorize('key', 'user', 'redirect');
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::authorize
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testAuthorizeError(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        self::expectException(LogicException::class);
        $this->createLocator($dto)->authorize('key', 'user', 'redirect');
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::authorize
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testAuthorizeRequestError(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        self::expectException(LogicException::class);
        $this->createLocator($dto, TRUE)->authorize('key', 'user', 'redirect');
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getNodes
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testGetNodes(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->getNodes();
        self::assertEquals(
            [
                'name' => [
                    'connector' => ['key' => 'null'],
                    'custom'    => ['key' => 'null'],
                    'user'      => ['key' => 'null'],
                ],
            ],
            $res
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getNodes
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testGetNodesRequestError(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto, TRUE)->getNodes();
        self::assertEquals([], $res);
    }

    /**
     * ------------------------------------ HELPERS --------------------------------------------
     */

    /**
     * @param ResponseDto $dto
     * @param bool        $exception
     *
     * @return ServiceLocator
     * @throws Exception
     */
    private function createLocator(ResponseDto $dto, bool $exception = FALSE): ServiceLocator
    {
        $sdk = new Sdk();
        $sdk->setValue('name')->setKey('host');
        $this->dm->persist($sdk);
        $this->dm->flush();
        $this->dm->clear();

        $curl = self::createMock(CurlManager::class);

        if ($exception) {
            $curl->method('send')->willThrowException(new Exception());
        } else {
            $curl->method('send')->willReturn($dto);
        }

        $redirect = $this->createMock(RedirectInterface::class);

        return (new ServiceLocator($this->dm, $curl, $redirect))->setLogger(new NullLogger());
    }

}
