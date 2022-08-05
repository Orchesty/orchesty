<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FakturoidCreateNewInvoiceConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewInvoiceConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @group live
     * @throws Exception
     */
    public function testSend(): void
    {
        $user               = 'ha****@mailinator.com';
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::USER     => $user,
                    BasicApplicationInterface::PASSWORD => 'c********d864859b625ec4c478184',
                    FakturoidApplication::ACCOUNT => 'fakturacnitest',
                ],
            ],
        );
        $applicationInstall->setKey($app->getName());
        $applicationInstall->setUser($user);
        $this->pfd($applicationInstall);
        $conn         = self::getContainer()->get('hbpf.connector.fakturoid.create-new-invoice');
        $dataFromFile = File::getContent(__DIR__ . '/NewInvoiceRequest.json');
        $dto          = DataProvider::getProcessDto($app->getName(), $user, $dataFromFile);
        $conn->processAction($dto);
        self::assertFake();
    }

}
