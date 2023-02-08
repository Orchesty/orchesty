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

/**
 * Class SettingsControllerTest
 *
 * @package ApplinthTests\Controller
 */
final class SettingsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @return void
     * @throws Exception
     */
    public function testGetApplicationDetail(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/SettingsController/detailApplicationRequest.json',
        );
    }

    /**
     * @throws Exception
     */
    public function testAuthorizeApplication(): void
    {
        $sdk = new Sdk();
        $sdk->setUrl('ip')->setName('name');
        $this->dm->persist($sdk);
        $this->dm->flush();
        $this->dm->clear();

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
            __DIR__ . '/data/SettingsController/authorizeApplicationRequest.json',
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
            __DIR__ . '/data/SettingsController/updateApplicationRequest.json',
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
            __DIR__ . '/data/SettingsController/setPasswordRequest.json',
        );
    }

}
