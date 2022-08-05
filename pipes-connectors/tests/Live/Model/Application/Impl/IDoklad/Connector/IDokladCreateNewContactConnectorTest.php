<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad\Connector;

use Exception;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class IDokladCreateNewContactConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad\Connector
 */
final class IDokladCreateNewContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @group live
     * @throws Exception
     */
    public function testSend(): void
    {
        $app = self::getContainer()->get('hbpf.application.i-doklad');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'token',
            'ae89f69a-44f4-4163-ac98-************',
            'de469040-fc97-4e03-861e-************',
        );
        $this->pfd($applicationInstall);
        $conn         = self::getContainer()->get('hbpf.connector.i-doklad.create-new-contact');
        $dataFromFile = File::getContent(__DIR__ . '/newContact.json');
        $dto          = DataProvider::getProcessDto($app->getName(), 'user', $dataFromFile);
        $conn->processAction($dto);
        self::assertFake();
    }

}
