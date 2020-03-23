<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector\Model;

use Exception;
use Hanaboso\PipesPhpSdk\Connector\Model\ConnectorManager;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\Unit\HbPFConnectorBundle\Loader\NullConnector;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorManagerTest
 *
 * @package PipesPhpSdkTests\Unit\Connector\Model
 */
final class ConnectorManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Model\ConnectorManager::processEvent
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory::createFromRequest

     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        /** @var ConnectorManager $manager */
        $manager = self::$container->get('hbpf.manager.connector');

        /** @var NullConnector $connector */
        $connector = self::$container->get('hbpf.connector.null');
        $dto       = $manager->processEvent($connector, new Request());

        self::assertEquals('', $dto->getData());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Model\ConnectorManager::processAction
     * @covers \Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract::getApplicationKey
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        /** @var ConnectorManager $manager */
        $manager = self::$container->get('hbpf.manager.connector');

        /** @var NullConnector $connector */
        $connector = self::$container->get('hbpf.connector.null');
        $dto       = $manager->processAction($connector, new Request());
        self::assertEquals('', $dto->getData());
    }

}
