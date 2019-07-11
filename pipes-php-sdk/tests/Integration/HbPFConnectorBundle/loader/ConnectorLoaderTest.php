<?php declare(strict_types=1);

namespace Tests\Integration\HbPFConnectorBundle\loader;

use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Loader\ConnectorLoader;
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
        $connector = new ConnectorLoader(self::$container);

        $fields = $connector->getAllConnectors();
        self::assertCount(1, $fields);

        $fields = $connector->getAllConnectors(['null']);
        self::assertCount(0, $fields);
    }

}
