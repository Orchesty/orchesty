<?php declare(strict_types=1);

namespace Tests\Controller\HbPFApplicationBundle\Controller;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\Base64;
use Hanaboso\PipesFramework\Application\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Handler\ApplicationHandler;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        $this->client->request('GET', '/applications');
        $response = $this->client->getResponse();

        self::assertIsArray(json_decode($response->getContent(), TRUE));
        self::assertEquals(200, $response->getStatusCode());

        $this->client->request('GET', '/applicationsss');
        $response = $this->client->getResponse();
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetApplication(): void
    {
        $application = 'null';
        $this->mockApplicationHandler('getApplicationByKey', [$application]);

        $this->client->request('GET', sprintf('/applications/%s', 'null'));
        $response = $this->client->getResponse();

        self::assertTrue(in_array($application, json_decode($response->getContent(), TRUE)));
        self::assertEquals(200, $response->getStatusCode());

        $this->client->request('GET', sprintf('/applications/%s', 'example'));
        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetUsersApplication(): void
    {
        $this->mockApplicationHandler(
            'getApplicationsByUser',
            json_decode((string) file_get_contents(sprintf('%s/data/data.json', __DIR__)))
        );

        $this->client->request('GET', '/applications/users/bar');
        $response = $this->client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[0][ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     */
    public function testGetApplicationDetail(): void
    {
        $this->insertApp();

        $this->client->request('GET', '/applications/someApp/users/bar');
        $response = $this->client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     *
     */
    public function testInstallApplication(): void
    {
        $this->client->request('POST', '/applications/example/users/bar/install');
        $response = $this->client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     */
    public function testUninstallApplication(): void
    {
        $this->insertApp('null');

        $this->client->request('DELETE', '/applications/null/users/bar/uninstall');
        $response = $this->client->getResponse();

        self::assertEquals('bar', json_decode($response->getContent(), TRUE)[ApplicationInstall::USER]);
        self::assertEquals('200', $response->getStatusCode());

        $this->client->request('GET', '/applications/someApp/users/bar');
        $response = $this->client->getResponse();

        self::assertEquals('2001', json_decode($response->getContent(), TRUE)['error_code']);

    }

    /**
     * @throws ReflectionException
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->mockApplicationHandler('updateApplicationSettings', ['new_settings' => 'test1']);

        $this->client->request('PUT', '/applications/someApp/users/bar/settings', [], [], [], '{"test":1}');
        $response = $this->client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
        self::assertEquals('test1', json_decode($response->getContent(), TRUE)['new_settings']);
    }

    /**
     * @throws ReflectionException
     */
    public function testSaveApplicationPassword(): void
    {
        $this->mockApplicationHandler('updateApplicationPassword', ['new_passwd' => 'secret']);

        $this->client->request('PUT', '/applications/someApp/users/bar/password', [], [], [], '{"passwd": test}');
        $response = $this->client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testAuthorization(): void
    {
        $this->mockApplicationHandler('authorizeApplication');
        $this->insertApp();
        $this->client->request('POST', '/applications/someApp/users/bar/authorize?redirect_url=somewhere');
        $response = $this->client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testSetAuthorizationToken(): void
    {
        $this->mockApplicationHandler('saveAuthToken', [BasicApplicationInterface::REDIRECT_URL => 'somewhere']);
        $this->insertApp();
        $this->client->request('GET', '/applications/someApp/users/bar/authorize/token');
        $response = $this->client->getResponse();

        self::assertEquals('302', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testSetAuthorizationTokenQuery(): void
    {
        $this->mockApplicationHandler('saveAuthToken', [BasicApplicationInterface::REDIRECT_URL => 'somewhere']);
        $this->insertApp();

        $encodedQuery = Base64::base64UrlEncode('user=bar&key=someApp');
        $this->client->request('GET', sprintf('/applications/authorize/token?state=%s', $encodedQuery));
        $response = $this->client->getResponse();

        self::assertEquals('302', $response->getStatusCode());
    }

    /**
     * @param string $method
     * @param array  $returnValue
     *
     * @throws ReflectionException
     */
    private function mockApplicationHandler(string $method, array $returnValue = []): void
    {
        $handler = $this->getMockBuilder(ApplicationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method($method)
            ->willReturn($returnValue);

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
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