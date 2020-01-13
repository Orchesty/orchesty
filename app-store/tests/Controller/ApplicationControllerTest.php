<?php declare(strict_types=1);

namespace Tests\Controller;

use Exception;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\HbPFAppStore\Handler\ApplicationHandler;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ApplicationControllerTest
 *
 * @package Tests\Controller
 */
final class ApplicationControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testListOfApplications(): void
    {
        self::$client->request('GET', '/applications');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertIsArray(Json::decode((string) $response->getContent()));
        self::assertEquals(200, $response->getStatusCode());

        self::$client->request('GET', '/applicationsss');
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $application = 'null';
        $this->mockApplicationHandler([$application]);

        self::$client->request('GET', sprintf('/applications/%s', 'null'));
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertTrue(
            in_array(
                $application,
                Json::decode((string) $response->getContent())
            )
        );
        self::assertEquals(200, $response->getStatusCode());

        self::$client->request('GET', sprintf('/applications/%s', 'example'));
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testGetUsersApplication(): void
    {
        $this->mockApplicationHandler(
            Json::decode((string) file_get_contents(sprintf('%s/data/data.json', __DIR__)))
        );

        self::$client->request('GET', '/applications/users/bar');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[0][ApplicationInstall::USER]
        );
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationDetail(): void
    {
        $this->insertApp();
        $application = self::createMock(ApplicationAbstract::class);
        $application->method('toArray')->willReturn(['user' => 'bar']);
        $application->method('getApplicationForm')->willReturn([]);
        self::$container->set('hbpf.application.someApp', $application);

        self::$client->request('GET', '/applications/someApp/users/bar');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[ApplicationInstall::USER]
        );
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     *
     */
    public function testInstallApplication(): void
    {
        $application = self::createMock(ApplicationAbstract::class);
        $application->method('toArray')->willReturn(['user' => 'bar']);
        self::$container->set('hbpf.application.example', $application);

        self::$client->request('POST', '/applications/example/users/bar/install');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[ApplicationInstall::USER]
        );
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->insertApp('null');

        self::$client->request('DELETE', '/applications/null/users/bar/uninstall');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[ApplicationInstall::USER]
        );
        self::assertEquals('200', $response->getStatusCode());

        $this->setupClient();
        self::$client->request('GET', '/applications/someApp/users/bar');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('3002', Json::decode((string) $response->getContent())['error_code']);
    }

    /**
     * @throws Exception
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->mockApplicationHandler(['new_settings' => 'test1']);

        self::$client->request('PUT', '/applications/someApp/users/bar/settings', [], [], [], '{"test":1}');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
        self::assertEquals(
            'test1',
            Json::decode((string) $response->getContent())['new_settings']
        );
    }

    /**
     * @throws Exception
     */
    public function testSaveApplicationPassword(): void
    {
        $this->mockApplicationHandler(['new_passwd' => 'secret']);

        self::$client->request('PUT', '/applications/someApp/users/bar/password', [], [], [], '{"passwd": test}');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @param mixed[] $returnValue
     *
     * @throws Exception
     */
    private function mockApplicationHandler(array $returnValue = []): void
    {
        $handler = $this->getMockBuilder(ApplicationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('saveAuthToken')
            ->willReturn($returnValue);
        $handler->method('updateApplicationPassword')
            ->willReturn($returnValue);
        $handler->method('updateApplicationSettings')
            ->willReturn($returnValue);
        $handler->method('getApplicationsByUser')
            ->willReturn($returnValue);
        $handler->method('getApplicationByKey')
            ->willReturn($returnValue);
        $handler->method('authorizeApplication')
            ->willReturnCallback(
                static function (): void {
                }
            );

        /** @var ContainerInterface $container */
        $container = self::$client->getContainer();
        $container->set('hbpf._application.handler.application', $handler);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws Exception
     */
    private function insertApp(string $key = 'someApp', string $user = 'bar'): void
    {
        $dto = new ApplicationInstall();
        $dto->setKey($key)
            ->setUser($user);

        $this->persistAndFlush($dto);
    }

}
