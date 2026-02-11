<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\ApiGateway\Locator;

use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Enum\NodeImplementationEnum;
use Hanaboso\Utils\String\Base64;
use Hanaboso\Utils\String\Json;
use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ServiceLocatorTest
 *
 * @package PipesFrameworkTests\Integration\ApiGateway\Locator
 */
#[CoversClass(ServiceLocator::class)]
#[AllowMockObjectsWithoutExpectations]
final class ServiceLocatorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetApplications(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [[
            'description'   => 'Description',
            'isInstallable' => TRUE,
            'key'           => 'null',
            'logo'          => 'Logo',
            'name'          => 'Null',
        ]]]), []);

        $secondDto = new ResponseDto(200, '', Json::encode(['items' => [[
            'authorized'    => TRUE,
            'description'   => 'Description',
            'enabled'       => TRUE,
            'isInstallable' => TRUE,
            'key'           => 'null',
            'logo'          => 'Logo',
            'name'          => 'Null',
        ]]]), []);

        $res = $this->createLocator($dto, FALSE, $secondDto)->getApplications('user');
        self::assertEquals(
            [
                [
                    'applications' => [
                        [
                            'activated'   => TRUE,
                            'authorized'  => TRUE,
                            'description' => 'Description',
                            'installable' => TRUE,
                            'installed'   => TRUE,
                            'key'         => 'null',
                            'logo'        => 'Logo',
                            'name'        => 'Null',
                        ],
                    ],
                    'name'         => 'name',
                    'url'          => 'host',
                ],
            ],
            $res,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetApps(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'null']]]), []);

        $res = $this->createLocator($dto)->getApps('name');
        self::assertEquals(
            [
                'filter' => [],
                'host'   => 'host',
                'items'  => [['key' => 'null']],
                'paging' => [
                    'itemsPerPage' => 50,
                    'lastPage'     => 1,
                    'nextPage'     => 1,
                    'page'         => 1,
                    'previousPage' => 1,
                    'total'        => 1,
                ],
                'sorter' => [],
            ],
            $res,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->getApp('null', 'name');
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @throws Exception
     */
    public function testGetUserApps(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'null']]]), []);

        $res = $this->createLocator($dto)->getUserApps('user', 'name');
        self::assertEquals(
            [
                'filter' => [],
                'host'   => 'host',
                'items'  => [['key' => 'null']],
                'paging' => [
                    'itemsPerPage' => 50,
                    'lastPage'     => 1,
                    'nextPage'     => 1,
                    'page'         => 1,
                    'previousPage' => 1,
                    'total'        => 1,
                ],
                'sorter' => [],
            ],
            $res,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetUserAppsNoResponse(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->getUserApps('user', 'name');
        self::assertEquals(
            [
                'filter' => [],
                'items'  => [],
                'paging' => [
                    'itemsPerPage' => 50,
                    'lastPage'     => 1,
                    'nextPage'     => 1,
                    'page'         => 1,
                    'previousPage' => 1,
                    'total'        => 0,
                ],
                'sorter' => [],
            ],
            $res,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAppDetail(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null', 'customActions' => []]), []);

        $res = $this->createLocator($dto)->getAppDetail('null', 'user', 'name');
        self::assertEquals(['key' => 'null', 'host' => 'host', 'customActions' => []], $res);
    }

    /**
     * @throws Exception
     */
    public function testInstallApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->installApp('null', 'user', 'name');
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @throws Exception
     */
    public function testUninstallApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->uninstallApp('null', 'user', 'name');
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @throws Exception
     */
    public function testUpdateApp(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->updateApp('null', 'user', 'name', ['form']);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @throws Exception
     */
    public function testUpdateAppPassword(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->updateAppPassword('null', 'user', 'name', ['pass']);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @throws Exception
     */
    public function testAuthorizationToken(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->authorizationToken('null', 'user', ['param1' => 'aaa', 'state' => ['abc']]);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @throws Exception
     */
    public function testAuthorizationQueryToken(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->authorizationQueryToken([
            'param1' => 'aaa',
            'state' => Base64::base64UrlEncode('name:name'),
        ]);
        self::assertEquals(['key' => 'null', 'host' => 'host'], $res);
    }

    /**
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['authorizeUrl' => 'redirect/url']), []);
        $this->createLocator($dto)->authorize('key', 'user', 'name', 'redirect');
        self::assertFake();
    }

    /**
     * @throws Exception
     */
    public function testAuthorizeError(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        self::expectException(LogicException::class);
        $this->createLocator($dto)->authorize('key', 'user', 'name', 'redirect');
    }

    /**
     * @throws Exception
     */
    public function testAuthorizeRequestError(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        self::expectException(LogicException::class);
        $this->createLocator($dto, TRUE)->authorize('key', 'user', 'name', 'redirect');
    }

    /**
     * @throws Exception
     */
    public function testGetNodes(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['key' => 'null']), []);

        $res = $this->createLocator($dto)->getNodes();
        self::assertEquals(
            [
                'backend' => [
                    'user' => ['user-task'],
                ],
                'name'    => [
                    'batch'     => ['key' => 'null'],
                    'connector' => ['key' => 'null'],
                    'custom'    => ['key' => 'null'],
                ],
            ],
            $res,
        );
    }

    /**
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
                'name' => [
                    NodeImplementationEnum::BATCH->value => [],
                    NodeImplementationEnum::CONNECTOR->value => [],
                    NodeImplementationEnum::CUSTOM->value => [],
                ],
            ],
            $res,
        );
    }

    /**
     * @throws Exception
     */
    public function testSubscribeWebhook(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->subscribeWebhook('key', 'user', 'name', []);
        self::assertEquals([], $res);
    }

    /**
     * @throws Exception
     */
    public function testUnSubscribeWebhook(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->unSubscribeWebhook('key', 'user', 'name', []);
        self::assertEquals([], $res);
    }

    /**
     * @throws Exception
     */
    public function testListSyncActions(): void
    {
        $dto = new ResponseDto(200, '', Json::encode([]), []);

        $res = $this->createLocator($dto)->listSyncActions('key', 'name');
        self::assertEquals([], $res);
    }

    /**
     * @throws Exception
     */
    public function testRunSyncActions(): void
    {
        $dto = new ResponseDto(205, '', Json::encode([]), []);
        $req = new Request(['aa' => 'bb']);
        $req->setMethod('post');

        self::expectException(LogicException::class);
        $this->createLocator($dto)->runSyncActions($req, 'key', 'name', 'someMethod');
    }

    /*
     * ------------------------------------ HELPERS --------------------------------------------
     */

    /**
     * @param ResponseDto $dto
     * @param bool        $exception
     * @param ResponseDto $secondDto
     *
     * @return ServiceLocator
     * @throws Exception
     */
    private function createLocator(
        ResponseDto $dto,
        bool $exception = FALSE,
        ?ResponseDto $secondDto = NULL,
    ): ServiceLocator {
        $sdk = new Sdk();
        $sdk->setName('name')->setUrl('host');
        $this->dm->persist($sdk);
        $this->dm->flush();
        $this->dm->clear();

        $curl = self::createMock(CurlManager::class);

        if ($exception) {
            $curl->method('send')->willThrowException(new Exception());
        } else if ($secondDto) {
            $curl->method('send')->willReturnOnConsecutiveCalls($dto, $secondDto);
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
