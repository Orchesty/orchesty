<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
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
        $app                = self::$container->get('hbpf.application.fakturoid');
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_SETTINGS => [
                    BasicApplicationInterface::USER     => $user,
                    BasicApplicationInterface::PASSWORD => 'c********d864859b625ec4c478184',
                ],
                ApplicationAbstract::FORM                    => [
                    FakturoidApplication::ACCOUNT => 'fakturacnitest',
                ],
            ],
        );
        $applicationInstall->setKey($app->getKey());
        $applicationInstall->setUser($user);
        $this->pfd($applicationInstall);
        $conn         = self::$container->get('hbpf.connector.fakturoid.create-new-invoice');
        $dataFromFile = (string) file_get_contents(__DIR__ . '/NewInvoiceRequest.json');
        $dto          = DataProvider::getProcessDto($app->getKey(), $user, $dataFromFile);
        $conn->processAction($dto);
        self::assertFake();
    }

}
