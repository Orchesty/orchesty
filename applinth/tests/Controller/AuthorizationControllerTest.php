<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Exception;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Class AuthorizationControllerTest
 *
 * @package ApplinthTests\Controller
 */
final class AuthorizationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testLogin(): void
    {
        $this->mockLocator('installApp');
        $this->assertResponse(
            __DIR__ . '/data/AuthorizationController/loginRequest.json',
            [
                'access_token' => 'abcd',
                'expires_in' => '123',
            ],
            requestHeadersReplacements:[self::$AUTHORIZATION => $this->getJweToken()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetNewToken(): void
    {
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
     * @param string $method
     */
    private function mockLocator(string $method): void
    {
        $handler = self::createPartialMock(ServiceLocator::class, [$method]);
        $this->setProperty($handler, 'sdkRepository', $this->dm->getRepository(Sdk::class));
        $handler->expects(self::any())->method($method)->willReturnCallback(function (): array {
            $app = new ApplicationInstall();
            $app
                ->setKey('user/app/id')
                ->setUser('endUser');

            $this->dm->persist($app);
            $this->dm->flush();

            return [];
        });

        $container = $this->client->getContainer();
        $container->set('hbpp.service.locator', $handler);
    }

}
