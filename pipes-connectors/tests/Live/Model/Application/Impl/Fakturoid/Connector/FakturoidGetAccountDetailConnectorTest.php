<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use HbPFConnectorsTests\ControllerTestCaseAbstract;

/**
 * Class FakturoidGetAccountDetailConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidGetAccountDetailConnectorTest extends ControllerTestCaseAbstract
{

    /**
     * @group live
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $app = self::$container->get('hbpf.application.fakturoid');

        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_SETTINGS => [
                    BasicApplicationInterface::USER     => 'hanabo****nator.com',
                    BasicApplicationInterface::PASSWORD => '******1bbef40dcd864859b625ec4c478184',
                ],
                ApplicationAbstract::FORM                    => [
                    FakturoidApplication::ACCOUNT => 'fakturacnitest',
                ],
            ],
        );
        $dto  = $app->getRequestDto(
            $applicationInstall,
            'GET',
            'https://app.fakturoid.cz/api/v2/accounts/fakturacnitest/account.json',
        );
        $curl = self::$container->get('hbpf.transport.curl_manager');
        $res  = $curl->send($dto);
        self::assertEquals(200, $res->getStatusCode());
        $dataFromFile = (string) file_get_contents(__DIR__ . '/AccountDetailResponse.json');
        self::assertEquals($dataFromFile, $res->getBody());
    }

}
