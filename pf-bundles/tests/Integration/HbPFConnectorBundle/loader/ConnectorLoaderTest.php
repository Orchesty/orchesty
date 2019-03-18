<?php declare(strict_types=1);

namespace Tests\Integration\HbPFConnectorBundle\loader;

use Hanaboso\PipesFramework\HbPFConnectorBundle\Loader\ConnectorLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class ConnectorLoaderTest
 *
 * @package Tests\Integration\HbPFConnectorBundle\loader
 */
final class ConnectorLoaderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetAllConnectors(): void
    {
        $connector = new ConnectorLoader($this->ownContainer);

        $fields = $connector->getAllConnectors();
        self::assertCount(3, $fields);

        $fields = $connector->getAllConnectors(['magento2.orders']);
        self::assertCount(2, $fields);
    }

}