<?php declare(strict_types=1);

namespace Tests\Controller\HbPFApplicationBundle\Controller;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\Base64;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Handler\ApplicationHandler;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ApplicationControllerTest
 *
 * @package Tests\Controller\HbPFApplicationBundle\Controller
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

        self::assertIsArray(json_decode($response->getContent(), TRUE));
        self::assertEquals(200, $response->getStatusCode());

        self::$client->request('GET', '/applicationsss');
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetApplication(): void
    {
        $application = 'null';
        $this->mockApplicationHandler([$application]);

        self::$client->request('GET', sprintf('/applications/%s', 'null'));
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertTrue(in_array($application, json_decode($response->getContent(), TRUE)));
        self::assertEquals(200, $response->getStatusCode());

        self::$client->request('GET', sprintf('/applications/%s', 'example'));
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetUsersApplication(): void
    {
        $this->mockApplicationHandler(
            json_decode((string) file_get_contents(sprintf('%s/data/data.json', __DIR__)))
        );

        self::$client->request('GET', '/applications/users/bar');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[0][ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     */
    public function testGetApplicationDetail(): void
    {
        $this->insertApp();

        self::$client->request('GET', '/applications/someApp/users/bar');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     *
     */
    public function testInstallApplication(): void
    {
        self::$client->request('POST', '/applications/example/users/bar/install');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     */
    public function testUninstallApplication(): void
    {
        $this->insertApp('null');

        self::$client->request('DELETE', '/applications/null/users/bar/uninstall');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());

        self::$client->request('GET', '/applications/someApp/users/bar');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('2001', json_decode($response->getContent(), TRUE)['error_code']);

    }

    /**
     * @throws ReflectionException
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->mockApplicationHandler(['new_settings' => 'test1']);

        self::$client->request('PUT', '/applications/someApp/users/bar/settings', [], [], [], '{"test":1}');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
        self::assertEquals('test1', json_decode($response->getContent(), TRUE)['new_settings']);
    }

    /**
     * @throws ReflectionException
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
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testAuthorization(): void
    {
        $this->mockApplicationHandler();
        $this->insertApp();
        self::$client->request('POST', '/applications/someApp/users/bar/authorize?redirect_url=somewhere');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testSetAuthorizationToken(): void
    {
        $this->mockApplicationHandler([BasicApplicationInterface::REDIRECT_URL => 'somewhere']);
        $this->insertApp();
        self::$client->request('GET', '/applications/someApp/users/bar/authorize/token');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('302', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testSetAuthorizationTokenQuery(): void
    {
        $this->mockApplicationHandler([BasicApplicationInterface::REDIRECT_URL => 'somewhere']);
        $this->insertApp();

        $encodedQuery = Base64::base64UrlEncode('user=bar&key=someApp');
        self::$client->request('GET', sprintf('/applications/authorize/token?state=%s', $encodedQuery));
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('302', $response->getStatusCode());
    }

    /**
     * @param array $returnValue
     *
     * @throws ReflectionException
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
            ->willReturnCallback(function (): void {
            });

        /** @var ContainerInterface $container */
        $container = self::$client->getContainer();
        $container->set('hbpf._application.handler.application', $handler);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws DateTimeException
     */
    private function insertApp(string $key = 'someApp', string $user = 'bar'): void
    {
        $dto = new ApplicationInstall();
        $dto->setKey($key)
            ->setUser($user);

        $this->persistAndFlush($dto);
    }

}
