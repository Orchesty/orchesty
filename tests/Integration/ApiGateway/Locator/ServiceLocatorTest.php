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
use Symfony\Component\HttpFoundation\Request;

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
        self::assertEquals(
            [
                'items'  => [['key' => 'null']],
                'paging' => [
                    'page'         => 1,
                    'itemsPerPage' => 50,
                    'total'        => 1,
                    'nextPage'     => 1,
                    'lastPage'     => 1,
                    'previousPage' => 1,
                ],
                'filter' => [],
                'sorter' => [],
                'host'   => 'host',
            ],
            $res,
        );
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
        self::assertEquals(
            [
                'items'  => [['key' => 'null']],
                'filter' => [],
                'sorter' => [],
                'host'   => 'host',
                'paging' => [
                    'page'         => 1,
                    'itemsPerPage' => 50,
                    'total'        => 1,
                    'nextPage'     => 1,
                    'lastPage'     => 1,
                    'previousPage' => 1,
                ],
            ],
            $res,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getUserApps
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::getSdks
     *
     * @throws Exception
     */
    public function testGetUserAppsNoResponse(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->getUserApps('user');
        self::assertEquals(
            [
                'items'  => [],
                'sorter' => [],
                'filter' => [],
                'paging' => [
                    'page'         => 1,
                    'itemsPerPage' => 50,
                    'total'        => 0,
                    'nextPage'     => 1,
                    'lastPage'     => 1,
                    'previousPage' => 1,
                ],
            ],
            $res,
        );
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
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null', 'customActions' => []]), []);

        $res = $this->createLocator($dto)->getAppDetail('null', 'user');
        self::assertEquals(['key' => 'null', 'host' => 'host', 'customActions' => []], $res);
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
        $dto = new ResponseDto(200, '', Json::encode(['authorizeUrl' => 'redirect/url']), []);
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
                'name'    => [
                    'connector' => ['key' => 'null'],
                    'custom'    => ['key' => 'null'],
                    'batch'     => ['key' => 'null'],
                ],
                'backend' => [
                    'user' => ['user-task'],
                ],
            ],
            $res,
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
        self::assertEquals(
            [
                'backend' => [
                    'user' => ['user-task'],
                ],
            ],
            $res,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::subscribeWebhook
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     *
     * @throws Exception
     */
    public function testSubscribeWebhook(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->subscribeWebhook('key', 'user', []);
        self::assertEquals([], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::unSubscribeWebhook
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     *
     * @throws Exception
     */
    public function testUnSubscribeWebhook(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->unSubscribeWebhook('key', 'user', []);
        self::assertEquals([], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::listSyncActions
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     *
     * @throws Exception
     */
    public function testListSyncActions(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->listSyncActions('key');
        self::assertEquals([], $res);
    }

    /**
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::runSyncActions
     * @covers \Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator::doRequest
     *
     * @throws Exception
     */
    public function testRunSyncActions(): void
    {
        $dto = new ResponseDto(205, '', Json::encode([]), []);
        $req = new Request(['aa' => 'bb']);
        $req->setMethod('post');

        self::expectException(LogicException::class);
        $this->createLocator($dto)->runSyncActions($req, 'key', 'someMethod');
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
        $sdk->setName('name')->setUrl('host');
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

        $locator = new ServiceLocator(
            $this->dm,
            $curl,
            $redirect,
            self::getContainer()->getParameter('backendHost'),
        );
        $locator->setLogger(new NullLogger());

        return $locator;
    }

}
