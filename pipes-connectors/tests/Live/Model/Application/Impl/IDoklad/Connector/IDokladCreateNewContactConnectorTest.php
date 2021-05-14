<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad\Connector;

use Exception;
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
        $app = self::$container->get('hbpf.application.i-doklad');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'token',
            'ae89f69a-44f4-4163-ac98-************',
            'de469040-fc97-4e03-861e-************',
        );
        $this->pfd($applicationInstall);
        $conn         = self::$container->get('hbpf.connector.i-doklad.create-new-contact');
        $dataFromFile = (string) file_get_contents(__DIR__ . '/newContact.json');
        $dto          = DataProvider::getProcessDto($app->getKey(), 'user', $dataFromFile);
        $conn->processAction($dto);
        self::assertFake();
    }

}
