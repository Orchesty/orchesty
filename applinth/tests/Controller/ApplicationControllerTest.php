<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Log\NullLogger;

/**
 * Class ApplicationControllerTest
 *
 * @package ApplinthTests\Controller
 */
#[AllowMockObjectsWithoutExpectations]
final class ApplicationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetInstalledApplications(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'user/app/id']]]), []);
        $this->createLocator($dto);

        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/installedApplicationsRequest.json',
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetAvailableApplications(): void
    {
        $dto = new ResponseDto(200, '', Json::encode(['items' => [['key' => 'user/app/id']]]), []);
        $this->createLocator($dto);

        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/availableApplicationsRequest.json',
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetApplicationPreview(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/previewApplicationRequest.json',
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetApplicationDetail(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/detailApplicationRequest.json',
        );
    }

    /**
     * @throws Exception
     */
    public function testAuthorizeApplication(): void
    {
        $curl = $this->createMock(CurlManager::class);
        $curl
            ->method('send')
            ->willReturn(new ResponseDto(200, '', Json::encode(['authorizeUrl' => 'redirect/url']), []));

        $loader = new ServiceLocator(
            $this->dm,
            $curl,
            self::createMock(RedirectInterface::class),
            self::getContainer()->getParameter('backendHost'),
        );

        self::getContainer()->set('hbpp.service.locator', $loader);

        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/authorizeApplicationRequest.json',
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testInstallApplication(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/installApplicationRequest.json',
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testUpdateApplication(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/updateApplicationRequest.json',
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/uninstallApplicationRequest.json',
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testSetPassword(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ApplicationController/setPasswordRequest.json',
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
     * @param ResponseDto $dto
     * @param bool        $exception
     *
     * @throws Exception
     */
    private function createLocator(ResponseDto $dto, bool $exception = FALSE): void
    {
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

        $container = $this->client->getContainer();
        $container->set('hbpp.service.locator', $locator);
    }

}
