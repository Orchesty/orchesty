<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\FlexiBee;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\Exception\DateTimeException;
use HbPFConnectorsTests\ControllerTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FlexiBeeApplicationTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\FlexiBee
 */
final class FlexiBeeApplicationTest extends ControllerTestCaseAbstract
{

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $dto = $this->getApp()->getRequestDto(new ProcessDto(), $this->getAppInstall(), 'GET');
        self::assertNotEmpty($dto->getHeaders()['Authorization']);
    }

    /**
     * @return FlexiBeeApplication
     * @throws Exception
     */
    private function getApp(): FlexiBeeApplication
    {
        return self::getContainer()->get('hbpf.application.flexibee');
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function getAppInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getName());

        $appInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM =>
                    [
                        'auth'        => 'http',
                        'flexibeeUrl' => 'https://demo.flexibee.eu/c/demo',
                        'password'    => 'pass123',
                        'user'        => 'user123',
                    ],
            ],
        );

        return $appInstall;
    }

}
