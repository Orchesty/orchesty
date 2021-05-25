<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\FlexiBee;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
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
     * @group live
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function testAuthorize(): void
    {
        $dto = $this->getApp()->getRequestDto($this->getAppInstall(), 'GET');
        self::assertNotEmpty($dto->getHeaders()['Authorization']);
    }

    /**
     * @return FlexiBeeApplication
     */
    private function getApp(): FlexiBeeApplication
    {
        return self::$container->get('hbpf.application.flexibee');
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function getAppInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getKey());

        $appInstall->setSettings(
            [
                FlexiBeeApplication::AUTHORIZATION_SETTINGS =>
                    [
                        'user'     => 'user123',
                        'password' => 'pass123',
                    ],
                BasicApplicationAbstract::FORM              =>
                    [
                        'auth'        => 'http',
                        'flexibeeUrl' => 'https://demo.flexibee.eu/c/demo',
                    ],
            ],
        );

        $this->pfd($appInstall);

        return $appInstall;
    }

}
