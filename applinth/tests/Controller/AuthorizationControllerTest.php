<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\BrowserKit\Cookie;

/**
 * Class AuthorizationControllerTest
 *
 * @package ApplinthTests\Controller
 */
#[AllowMockObjectsWithoutExpectations]
final class AuthorizationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testLogin(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'php-sdk']]]), []);
        $this->mockLocator($dto);
        $this->assertResponse(
            __DIR__ . '/data/AuthorizationController/loginRequest.json',
            [
                'access_token' => 'abcd',
                'expires_in' => '123',
            ],
            requestHeadersReplacements: [self::$AUTHORIZATION => $this->getJweToken()],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoginWithScopes(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'php-sdk']]]), []);
        $this->mockLocator($dto);
        $this->assertResponse(
            __DIR__ . '/data/AuthorizationController/loginScopedRequest.json',
            [
                'access_token'  => 'abcd',
                'expires_in'    => '123',
                'refresh_token' => 'abcd',
            ],
            requestHeadersReplacements: [self::$AUTHORIZATION => $this->getJweToken()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetNewToken(): void
    {
        $cookie = new Cookie('refresh_token', $this->getJwsToken());
        $this->client->getCookieJar()->set($cookie);
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/AuthorizationController/loggedRequest.json',
            [
                'access_token' => 'abcd',
                'expires_in'   => '123',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testRefreshToken(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/AuthorizationController/refreshRequest.json',
            [
                'access_token' => 'abcd',
                'expires_in'   => '123',
            ],
            requestBodyReplacements: ['refresh_token'=> $this->getJwsToken()],
        );
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $sdk = new Sdk();
        $sdk->setUrl('php-sdk')->setName('php-sdk');
        $this->dm->persist($sdk);
        $this->dm->flush();
        $this->dm->clear();
    }

    /**
     * @param ResponseDto|null $dto
     *
     * @return void
     */
    private function mockLocator(?ResponseDto $dto = NULL): void
    {
        $handler = self::createPartialMock(ServiceLocator::class, ['installApp']);
        $this->setProperty($handler, 'sdkRepository', $this->dm->getRepository(Sdk::class));
        $handler->expects(self::atLeastOnce())->method('installApp')->willReturnCallback(function (): array {
            $app = new ApplicationInstall();
            $app
                ->setKey('user/app/id')
                ->setUser('endUser');

            $this->dm->persist($app);
            $this->dm->flush();

            return [];
        });

        if ($dto !== NULL) {
            $curl = self::createMock(CurlManager::class);
            $curl->method('send')->willReturn($dto);
            $this->setProperty($handler, 'curlManager', $curl);
        }

        $container = $this->client->getContainer();
        $container->set('hbpp.service.locator', $handler);
    }

}
