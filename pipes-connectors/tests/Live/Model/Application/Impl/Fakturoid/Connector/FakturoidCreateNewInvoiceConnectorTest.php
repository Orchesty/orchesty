<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\Group;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class FakturoidCreateNewInvoiceConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewInvoiceConnectorTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @throws Exception
     */
    #[Group('live')]
    public function testSend(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $user               = 'ha****@mailinator.com';
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::PASSWORD => 'c********d864859b625ec4c478184',
                    BasicApplicationInterface::USER     => $user,
                    FakturoidApplication::ACCOUNT => 'fakturacnitest',
                ],
            ],
        );
        $applicationInstall->setKey($app->getName());
        $applicationInstall->setUser($user);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["fakturoid"],"users":["ha****@mailinator.com"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($applicationInstall->toArray())),
            ),
        );

        $conn         = self::getContainer()->get('hbpf.connector.fakturoid.create-new-invoice');
        $dataFromFile = File::getContent(__DIR__ . '/NewInvoiceRequest.json');
        $dto          = DataProvider::getProcessDto($app->getName(), $user, $dataFromFile);
        $conn->processAction($dto);

        self::assertFake();
    }

}
