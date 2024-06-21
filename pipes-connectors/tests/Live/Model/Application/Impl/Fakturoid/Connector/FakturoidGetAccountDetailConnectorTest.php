<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\ControllerTestCaseAbstract;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class FakturoidGetAccountDetailConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidGetAccountDetailConnectorTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    #[Group('live')]
    public function testAuthorize(): void
    {
        self::markTestSkipped('live tests');
        $app = self::getContainer()->get('hbpf.application.fakturoid');

        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::PASSWORD => '******1bbef40dcd864859b625ec4c478184',
                    BasicApplicationInterface::USER     => 'hanabo****nator.com',
                    FakturoidApplication::ACCOUNT => 'fakturacnitest',
                ],
            ],
        );
        $dto  = $app->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            'GET',
            'https://app.fakturoid.cz/api/v2/accounts/fakturacnitest/account.json',
        );
        $curl = self::getContainer()->get('hbpf.transport.curl_manager');
        $res  = $curl->send($dto);

        self::assertEquals(200, $res->getStatusCode());
        $dataFromFile = File::getContent(__DIR__ . '/AccountDetailResponse.json');
        self::assertEquals($dataFromFile, $res->getBody());
    }

}
